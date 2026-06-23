#!/bin/bash

# SPDX-FileCopyrightText: 2026 G DATA CyberDefense AG
#
# SPDX-License-Identifier: AGPL-3.0-or-later

set -euo pipefail

RUNS=7
LIMIT=500
OFFSET=0
MODE="both" # both|with-tags|without-tags|single-tag
DB_CONTAINER="mariadb"
NC_CONTAINER="nextcloud-container"
DB_USER="nextcloud"
DB_PASS="nextcloud"
DB_NAME="nextcloud"
DB_ROOT_PASS="rootpassword"
SLOW_LOG=false
SLOW_LOG_FILE=""

usage() {
  cat <<'EOF'
Benchmark old vs new DbFileMapper queries on MariaDB.

Usage:
  ./scripts/benchmark-dbfilemapper-queries.sh [options]

Options:
  --runs <n>       Number of timed runs per query (default: 7)
  --limit <n>      Query LIMIT (default: 500)
  --offset <n>     Query OFFSET (default: 0)
  --mode <m>       both|with-tags|without-tags|single-tag (default: both)
                   single-tag simulates the real scan path: only the 'Unscanned' tag (1 tag-id)
  --slow-log       Enable MariaDB slow query log integration: shows Rows_examined per variant.
                   Requires root access to MariaDB (uses DB_ROOT_PASS=rootpassword).
                   Also enabled persistently via docker-compose.yaml (needs container restart once).
  --help           Show this help

Notes:
  - Requires running Docker containers: mariadb + nextcloud-container.
  - Compares old query shape (pre-fix) vs new query shape (current fix).
  - Prints avg/min/max runtime in milliseconds.
  - With --slow-log: additionally prints avg/min/max Rows_examined (independent of cache/load).
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --runs)
      RUNS="$2"
      shift 2
      ;;
    --limit)
      LIMIT="$2"
      shift 2
      ;;
    --offset)
      OFFSET="$2"
      shift 2
      ;;
    --mode)
      MODE="$2"
      shift 2
      ;;
    --slow-log)
      SLOW_LOG=true
      shift
      ;;
    --help|-h)
      usage
      exit 0
      ;;
    *)
      echo "Unknown argument: $1" >&2
      usage
      exit 1
      ;;
  esac
done

if [[ ! "$RUNS" =~ ^[0-9]+$ ]] || [[ "$RUNS" -lt 1 ]]; then
  echo "--runs must be a positive integer" >&2
  exit 1
fi
if [[ ! "$LIMIT" =~ ^[0-9]+$ ]]; then
  echo "--limit must be a non-negative integer" >&2
  exit 1
fi
if [[ ! "$OFFSET" =~ ^[0-9]+$ ]]; then
  echo "--offset must be a non-negative integer" >&2
  exit 1
fi
if [[ "$MODE" != "both" && "$MODE" != "with-tags" && "$MODE" != "without-tags" && "$MODE" != "single-tag" ]]; then
  echo "--mode must be one of: both, with-tags, without-tags, single-tag" >&2
  exit 1
fi

MYSQL_CLI=""

resolve_mysql_cli() {
  MYSQL_CLI=$(docker exec -i "$DB_CONTAINER" sh -lc 'command -v mysql || command -v mariadb' | tr -d '\r')
  if [[ -z "$MYSQL_CLI" ]]; then
    echo "Could not find mysql/mariadb client inside container '$DB_CONTAINER'" >&2
    exit 1
  fi
}

mysql_exec() {
  local sql="$1"
  docker exec -i "$DB_CONTAINER" "$MYSQL_CLI" -N -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$sql"
}

mysql_root_exec() {
  local sql="$1"
  docker exec -i "$DB_CONTAINER" "$MYSQL_CLI" -N -uroot -p"$DB_ROOT_PASS" -e "$sql"
}

setup_slow_log() {
  if ! mysql_root_exec "SET GLOBAL slow_query_log = ON; SET GLOBAL long_query_time = 0;" 2>/dev/null; then
    echo "Warning: Could not enable slow query log (root access failed — check DB_ROOT_PASS)" >&2
    SLOW_LOG=false
    return
  fi
  SLOW_LOG_FILE=$(mysql_root_exec "SELECT @@slow_query_log_file;" 2>/dev/null | tr -d '\r')
  # If the path is relative, resolve it against @@datadir (MariaDB default behaviour)
  if [[ -n "$SLOW_LOG_FILE" && "$SLOW_LOG_FILE" != /* ]]; then
    local datadir
    datadir=$(mysql_root_exec "SELECT @@datadir;" 2>/dev/null | tr -d '\r')
    SLOW_LOG_FILE="${datadir%/}/${SLOW_LOG_FILE}"
  fi
  if [[ -z "$SLOW_LOG_FILE" ]]; then
    SLOW_LOG_FILE="/var/lib/mysql/slow.log"
  fi
  # Ensure the file exists and is writable
  docker exec "$DB_CONTAINER" bash -c "touch \"$SLOW_LOG_FILE\" 2>/dev/null || true"
  mysql_root_exec "FLUSH SLOW LOGS;" 2>/dev/null || true
}

flush_slow_log() {
  docker exec "$DB_CONTAINER" bash -c "truncate -s 0 \"$SLOW_LOG_FILE\" 2>/dev/null || true"
}

read_rows_examined() {
  docker exec "$DB_CONTAINER" bash -c \
    "grep -oP 'Rows_examined: \K[0-9]+' \"$SLOW_LOG_FILE\" | tail -1" 2>/dev/null || true
}

echo "Resolving DB table prefix and runtime parameters..."
resolve_mysql_cli
FILECACHE_TABLE=$(mysql_exec "SHOW TABLES LIKE '%filecache';" | head -n1)
if [[ -z "$FILECACHE_TABLE" ]]; then
  echo "Could not detect filecache table in database '$DB_NAME'" >&2
  exit 1
fi
TABLE_PREFIX="${FILECACHE_TABLE%filecache}"

INSTANCE_ID=$(docker exec --user www-data -i "$NC_CONTAINER" php occ config:system:get instanceid | tr -d '\r')
if [[ -z "$INSTANCE_ID" ]]; then
  echo "Could not read Nextcloud instanceid via occ" >&2
  exit 1
fi

DIR_MIMETYPE_ID=$(mysql_exec "SELECT id FROM ${TABLE_PREFIX}mimetypes WHERE mimetype='httpd/unix-directory' LIMIT 1;")
if [[ -z "$DIR_MIMETYPE_ID" ]]; then
  echo "Could not resolve folder mimetype id" >&2
  exit 1
fi

TAG_IDS=$(mysql_exec "SELECT id FROM ${TABLE_PREFIX}systemtag WHERE name IN ('Clean','Malicious','Pup','Unscanned','Won''t scan') ORDER BY id;")
TAG_IDS_CSV=$(echo "$TAG_IDS" | tr '\n' ',' | sed 's/,$//')

UNSCANNED_TAG_ID=$(mysql_exec "SELECT id FROM ${TABLE_PREFIX}systemtag WHERE name='Unscanned' LIMIT 1;" | tr -d '\r')

if [[ -z "$TAG_IDS_CSV" && "$MODE" != "single-tag" ]]; then
  echo "Warning: No VaaS tags found in systemtag table."
  echo "Skipping with-tags and without-tags benchmarks because include/exclude IDs are empty."
  exit 0
fi

if [[ -z "$UNSCANNED_TAG_ID" && ( "$MODE" == "single-tag" || "$MODE" == "both" ) ]]; then
  echo "Warning: 'Unscanned' tag not found in systemtag table — skipping single-tag benchmark."
  UNSCANNED_TAG_ID=""
fi

build_old_without_tags_sql() {
  cat <<EOF
SELECT SQL_NO_CACHE fc.fileid
FROM ${TABLE_PREFIX}filecache fc
LEFT JOIN ${TABLE_PREFIX}storages s ON fc.storage = s.numeric_id
LEFT JOIN ${TABLE_PREFIX}systemtag_object_mapping o ON o.objectid = CAST(fc.fileid AS CHAR(64))
WHERE o.systemtagid NOT IN (${TAG_IDS_CSV})
   OR o.systemtagid IS NULL
  AND fc.mimetype <> ${DIR_MIMETYPE_ID}
  AND (fc.path LIKE 'files/%' OR s.id NOT LIKE 'home::%')
  AND fc.path NOT LIKE 'appdata_${INSTANCE_ID}/%'
  AND fc.path NOT LIKE '\\_\\_groupfolders/versions/%'
  AND fc.path NOT LIKE '\\_\\_groupfolders/trash/%'
ORDER BY fc.fileid DESC
LIMIT ${LIMIT} OFFSET ${OFFSET}
EOF
}

build_new_without_tags_sql() {
  cat <<EOF
SELECT SQL_NO_CACHE fc.fileid
FROM ${TABLE_PREFIX}filecache fc
LEFT JOIN ${TABLE_PREFIX}storages s ON fc.storage = s.numeric_id
LEFT JOIN ${TABLE_PREFIX}systemtag_object_mapping o
  ON o.objectid = CAST(fc.fileid AS CHAR(64))
 AND o.objecttype = 'files'
 AND o.systemtagid IN (${TAG_IDS_CSV})
WHERE o.objectid IS NULL
  AND fc.mimetype <> ${DIR_MIMETYPE_ID}
  AND (fc.path LIKE 'files/%' OR s.id NOT LIKE 'home::%')
  AND fc.path NOT LIKE 'appdata_${INSTANCE_ID}/%'
  AND fc.path NOT LIKE '\\_\\_groupfolders/versions/%'
  AND fc.path NOT LIKE '\\_\\_groupfolders/trash/%'
ORDER BY fc.fileid DESC
LIMIT ${LIMIT} OFFSET ${OFFSET}
EOF
}

build_old_with_tags_sql() {
  cat <<EOF
SELECT SQL_NO_CACHE fc.fileid
FROM ${TABLE_PREFIX}filecache fc
LEFT JOIN ${TABLE_PREFIX}storages s ON fc.storage = s.numeric_id
LEFT JOIN ${TABLE_PREFIX}systemtag_object_mapping o ON o.objectid = CAST(fc.fileid AS CHAR(64))
WHERE o.systemtagid IN (${TAG_IDS_CSV})
   OR o.systemtagid IS NULL
  AND fc.mimetype <> ${DIR_MIMETYPE_ID}
  AND (fc.path LIKE 'files/%' OR s.id NOT LIKE 'home::%')
  AND fc.path NOT LIKE 'appdata_${INSTANCE_ID}/%'
  AND fc.path NOT LIKE '\\_\\_groupfolders/versions/%'
  AND fc.path NOT LIKE '\\_\\_groupfolders/trash/%'
ORDER BY fc.fileid DESC
LIMIT ${LIMIT} OFFSET ${OFFSET}
EOF
}

build_new_with_tags_sql() {
  cat <<EOF
SELECT SQL_NO_CACHE DISTINCT fc.fileid
FROM ${TABLE_PREFIX}filecache fc
LEFT JOIN ${TABLE_PREFIX}storages s ON fc.storage = s.numeric_id
INNER JOIN ${TABLE_PREFIX}systemtag_object_mapping o
  ON o.objectid = CAST(fc.fileid AS CHAR(64))
 AND o.objecttype = 'files'
 AND o.systemtagid IN (${TAG_IDS_CSV})
WHERE o.objectid IS NOT NULL
  AND fc.mimetype <> ${DIR_MIMETYPE_ID}
  AND (fc.path LIKE 'files/%' OR s.id NOT LIKE 'home::%')
  AND fc.path NOT LIKE 'appdata_${INSTANCE_ID}/%'
  AND fc.path NOT LIKE '\\_\\_groupfolders/versions/%'
  AND fc.path NOT LIKE '\\_\\_groupfolders/trash/%'
ORDER BY fc.fileid DESC
LIMIT ${LIMIT} OFFSET ${OFFSET}
EOF
}
# Single-tag queries: simulate the real scan path (only Unscanned tag = 1 tag-id, no DISTINCT needed)
build_old_single_tag_sql() {
  cat <<EOF
SELECT SQL_NO_CACHE fc.fileid
FROM ${TABLE_PREFIX}filecache fc
LEFT JOIN ${TABLE_PREFIX}storages s ON fc.storage = s.numeric_id
LEFT JOIN ${TABLE_PREFIX}systemtag_object_mapping o ON o.objectid = CAST(fc.fileid AS CHAR(64))
WHERE o.systemtagid IN (${UNSCANNED_TAG_ID})
   OR o.systemtagid IS NULL
  AND fc.mimetype <> ${DIR_MIMETYPE_ID}
  AND (fc.path LIKE 'files/%' OR s.id NOT LIKE 'home::%')
  AND fc.path NOT LIKE 'appdata_${INSTANCE_ID}/%'
  AND fc.path NOT LIKE '\_\_groupfolders/versions/%'
  AND fc.path NOT LIKE '\_\_groupfolders/trash/%'
ORDER BY fc.fileid DESC
LIMIT ${LIMIT} OFFSET ${OFFSET}
EOF
}

build_new_single_tag_sql() {
  cat <<EOF
SELECT SQL_NO_CACHE fc.fileid
FROM ${TABLE_PREFIX}filecache fc
LEFT JOIN ${TABLE_PREFIX}storages s ON fc.storage = s.numeric_id
INNER JOIN ${TABLE_PREFIX}systemtag_object_mapping o
  ON o.objectid = CAST(fc.fileid AS CHAR(64))
 AND o.objecttype = 'files'
 AND o.systemtagid = ${UNSCANNED_TAG_ID}
WHERE fc.mimetype <> ${DIR_MIMETYPE_ID}
  AND (fc.path LIKE 'files/%' OR s.id NOT LIKE 'home::%')
  AND fc.path NOT LIKE 'appdata_${INSTANCE_ID}/%'
  AND fc.path NOT LIKE '\_\_groupfolders/versions/%'
  AND fc.path NOT LIKE '\_\_groupfolders/trash/%'
ORDER BY fc.fileid DESC
LIMIT ${LIMIT} OFFSET ${OFFSET}
EOF
}

# EXISTS variant — current optimized implementation
build_exists_without_tags_sql() {
  cat <<EOF
SELECT SQL_NO_CACHE fc.fileid
FROM ${TABLE_PREFIX}filecache fc
LEFT JOIN ${TABLE_PREFIX}storages s ON fc.storage = s.numeric_id
WHERE NOT EXISTS (
  SELECT 1 FROM ${TABLE_PREFIX}systemtag_object_mapping o
  WHERE o.objectid = CAST(fc.fileid AS CHAR(64))
    AND o.objecttype = 'files'
    AND o.systemtagid IN (${TAG_IDS_CSV})
)
  AND fc.mimetype <> ${DIR_MIMETYPE_ID}
  AND (fc.path LIKE 'files/%' OR s.id NOT LIKE 'home::%')
  AND fc.path NOT LIKE 'appdata_${INSTANCE_ID}/%'
  AND fc.path NOT LIKE '\_\_groupfolders/versions/%'
  AND fc.path NOT LIKE '\_\_groupfolders/trash/%'
ORDER BY fc.fileid DESC
LIMIT ${LIMIT} OFFSET ${OFFSET}
EOF
}

build_exists_with_tags_sql() {
  cat <<EOF
SELECT SQL_NO_CACHE fc.fileid
FROM ${TABLE_PREFIX}filecache fc
LEFT JOIN ${TABLE_PREFIX}storages s ON fc.storage = s.numeric_id
WHERE EXISTS (
  SELECT 1 FROM ${TABLE_PREFIX}systemtag_object_mapping o
  WHERE o.objectid = CAST(fc.fileid AS CHAR(64))
    AND o.objecttype = 'files'
    AND o.systemtagid IN (${TAG_IDS_CSV})
)
  AND fc.mimetype <> ${DIR_MIMETYPE_ID}
  AND (fc.path LIKE 'files/%' OR s.id NOT LIKE 'home::%')
  AND fc.path NOT LIKE 'appdata_${INSTANCE_ID}/%'
  AND fc.path NOT LIKE '\_\_groupfolders/versions/%'
  AND fc.path NOT LIKE '\_\_groupfolders/trash/%'
ORDER BY fc.fileid DESC
LIMIT ${LIMIT} OFFSET ${OFFSET}
EOF
}

build_exists_single_tag_sql() {
  cat <<EOF
SELECT SQL_NO_CACHE fc.fileid
FROM ${TABLE_PREFIX}filecache fc
LEFT JOIN ${TABLE_PREFIX}storages s ON fc.storage = s.numeric_id
WHERE EXISTS (
  SELECT 1 FROM ${TABLE_PREFIX}systemtag_object_mapping o
  WHERE o.objectid = CAST(fc.fileid AS CHAR(64))
    AND o.objecttype = 'files'
    AND o.systemtagid = ${UNSCANNED_TAG_ID}
)
  AND fc.mimetype <> ${DIR_MIMETYPE_ID}
  AND (fc.path LIKE 'files/%' OR s.id NOT LIKE 'home::%')
  AND fc.path NOT LIKE 'appdata_${INSTANCE_ID}/%'
  AND fc.path NOT LIKE '\_\_groupfolders/versions/%'
  AND fc.path NOT LIKE '\_\_groupfolders/trash/%'
ORDER BY fc.fileid DESC
LIMIT ${LIMIT} OFFSET ${OFFSET}
EOF
}
run_timed_query() {
  local sql="$1"
  local start_ns end_ns elapsed_ns elapsed_ms

  if [[ "$SLOW_LOG" == "true" ]]; then
    flush_slow_log
  fi

  start_ns=$(date +%s%N)
  mysql_exec "$sql" > /dev/null
  end_ns=$(date +%s%N)

  elapsed_ns=$((end_ns - start_ns))
  elapsed_ms=$(awk -v ns="$elapsed_ns" 'BEGIN { printf "%.3f", ns/1000000 }')

  if [[ "$SLOW_LOG" == "true" ]]; then
    local rows_examined
    rows_examined=$(read_rows_examined)
    echo "${elapsed_ms} ${rows_examined:-0}"
  else
    echo "$elapsed_ms"
  fi
}

summarize_series() {
  local series="$1"
  # Uses only $1 (ms) — works with both "123.456" and "123.456 78900" (slow-log mode)
  echo "$series" | awk '
    BEGIN { min=1e99; max=0; sum=0; n=0 }
    {
      v=$1+0;
      if (v < min) min=v;
      if (v > max) max=v;
      sum += v;
      n += 1;
    }
    END {
      if (n == 0) {
        print "n/a";
      } else {
        printf "avg=%.3f ms, min=%.3f ms, max=%.3f ms", sum/n, min, max;
      }
    }
  '
}

summarize_rows_examined_series() {
  local series="$1"
  echo "$series" | awk '
    NF >= 2 {
      v=$2+0;
      if (n==0 || v < min) min=v;
      if (n==0 || v > max) max=v;
      sum += v; n += 1;
    }
    END {
      if (n == 0) { print "n/a"; }
      else { printf "avg=%d, min=%d, max=%d rows", sum/n, min, max; }
    }
  '
}

benchmark_pair() {
  local title="$1"
  local old_sql="$2"
  local new_sql="$3"
  local old_runs new_runs i

  echo
  echo "=== $title ==="

  old_runs=""
  new_runs=""

  for ((i=1; i<=RUNS; i++)); do
    old_runs+="$(run_timed_query "$old_sql")"$'\n'
    new_runs+="$(run_timed_query "$new_sql")"$'\n'
  done

  echo "Old query : $(summarize_series "$old_runs")"
  echo "New query : $(summarize_series "$new_runs")"

  awk -v o="$(echo "$old_runs" | awk '{sum+=$1;n+=1} END{if(n==0) print 0; else print sum/n}')" \
      -v n="$(echo "$new_runs" | awk '{sum+=$1;n+=1} END{if(n==0) print 0; else print sum/n}')" '
    BEGIN {
      if (o <= 0) {
        print "Delta     : n/a";
      } else {
        d=((n-o)/o)*100;
        printf "Delta     : %+.2f%% (new vs old)\n", d;
      }
    }
  '
}

echo "Benchmark configuration:"
echo "  Table prefix : ${TABLE_PREFIX}"
echo "  Instance ID  : ${INSTANCE_ID}"
echo "  Runs         : ${RUNS}"
echo "  Limit/Offset : ${LIMIT}/${OFFSET}"
echo "  Mode         : ${MODE}"
echo "  Slow log     : ${SLOW_LOG}"

if [[ "$SLOW_LOG" == "true" ]]; then
  setup_slow_log
  if [[ "$SLOW_LOG" == "true" ]]; then
    echo "  Slow log file: ${SLOW_LOG_FILE} (in container)"
  fi
fi
echo

benchmark_triple() {
  local title="$1"
  local old_sql="$2"
  local join_sql="$3"
  local exists_sql="$4"
  local old_runs join_runs exists_runs i

  echo
  echo "=== $title ==="

  old_runs=""
  join_runs=""
  exists_runs=""

  for ((i=1; i<=RUNS; i++)); do
    old_runs+="$(run_timed_query "$old_sql")"$'\n'
    join_runs+="$(run_timed_query "$join_sql")"$'\n'
    exists_runs+="$(run_timed_query "$exists_sql")"$'\n'
  done

  echo "Old (buggy, pre-fix) : $(summarize_series "$old_runs")"
  echo "JOIN (current impl)  : $(summarize_series "$join_runs")"
  echo "EXISTS (discarded)   : $(summarize_series "$exists_runs")"

  local old_avg join_avg exists_avg
  old_avg=$(echo "$old_runs"    | awk '{sum+=$1;n+=1} END{if(n==0) print 0; else print sum/n}')
  join_avg=$(echo "$join_runs"  | awk '{sum+=$1;n+=1} END{if(n==0) print 0; else print sum/n}')
  exists_avg=$(echo "$exists_runs" | awk '{sum+=$1;n+=1} END{if(n==0) print 0; else print sum/n}')

  awk -v o="$old_avg" -v j="$join_avg" -v e="$exists_avg" '
    BEGIN {
      if (o <= 0) {
        print "Delta             : n/a"
      } else {
        printf "Delta JOIN (curr) : %+.2f%% vs old\n", ((j-o)/o)*100
        printf "Delta EXISTS      : %+.2f%% vs old\n", ((e-o)/o)*100
      }
    }
  '

  if [[ "$SLOW_LOG" == "true" ]]; then
    echo
    echo "Rows examined (old)        : $(summarize_rows_examined_series "$old_runs")"
    echo "Rows examined (JOIN/curr)  : $(summarize_rows_examined_series "$join_runs")"
    echo "Rows examined (EXISTS)     : $(summarize_rows_examined_series "$exists_runs")"

    local old_rows_avg join_rows_avg exists_rows_avg
    old_rows_avg=$(echo "$old_runs"    | awk 'NF>=2{sum+=$2;n+=1} END{if(n==0) print 0; else print sum/n}')
    join_rows_avg=$(echo "$join_runs"  | awk 'NF>=2{sum+=$2;n+=1} END{if(n==0) print 0; else print sum/n}')
    exists_rows_avg=$(echo "$exists_runs" | awk 'NF>=2{sum+=$2;n+=1} END{if(n==0) print 0; else print sum/n}')

    awk -v o="$old_rows_avg" -v j="$join_rows_avg" -v e="$exists_rows_avg" '
      BEGIN {
        if (o <= 0) {
          print "Rows delta             : n/a"
        } else {
          printf "Rows delta JOIN (curr) : %+.2f%% vs old\n", ((j-o)/o)*100
          printf "Rows delta EXISTS      : %+.2f%% vs old\n", ((e-o)/o)*100
        }
      }
    '
  fi
}

if [[ "$MODE" == "both" || "$MODE" == "without-tags" ]]; then
  benchmark_triple \
    "WITHOUT TAGS query (multi-tag, disableUnscannedTag=true path)" \
    "$(build_old_without_tags_sql)" \
    "$(build_new_without_tags_sql)" \
    "$(build_exists_without_tags_sql)"
fi

if [[ "$MODE" == "both" || "$MODE" == "with-tags" ]]; then
  benchmark_triple \
    "WITH TAGS query (multi-tag)" \
    "$(build_old_with_tags_sql)" \
    "$(build_new_with_tags_sql)" \
    "$(build_exists_with_tags_sql)"
fi

if [[ "$MODE" == "both" || "$MODE" == "single-tag" ]]; then
  if [[ -z "$UNSCANNED_TAG_ID" ]]; then
    echo
    echo "=== SINGLE TAG query (Unscanned) ==="
    echo "Skipped: 'Unscanned' tag not found in DB."
  else
    benchmark_triple \
      "SINGLE TAG query — Unscanned (real daily scan path)" \
      "$(build_old_single_tag_sql)" \
      "$(build_new_single_tag_sql)" \
      "$(build_exists_single_tag_sql)"
  fi
fi

echo
echo "Done. Tip: run multiple datasets (e.g. after generating many files) and compare deltas."
