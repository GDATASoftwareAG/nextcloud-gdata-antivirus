#!/bin/bash

# SPDX-FileCopyrightText: 2026 G DATA CyberDefense AG
#
# SPDX-License-Identifier: AGPL-3.0-or-later

set -euo pipefail

NC_CONTAINER="nextcloud-container"
NC_USER="admin"
TARGET_DIR="BenchmarkFiles"
FILE_COUNT=5000
JOBS=8
FILE_SIZE=256
SCAN_AFTER_CREATE=1
TAG_UNSCANNED_AFTER_CREATE=1

usage() {
  cat <<'EOF'
Populate many files for Nextcloud benchmark scenarios.

This script creates files directly in the Nextcloud data directory in parallel,
then runs occ files:scan so entries are indexed in filecache.

Usage:
  ./scripts/populate-nextcloud-benchmark-files.sh [options]

Options:
  --count <n>            Number of files to create (default: 50000)
  --size-bytes <n>       File size in bytes (default: 256)
  --jobs <n>             Parallel workers for file creation (default: 8)
  --user <uid>           Nextcloud user (default: admin)
  --dir <name>           Target folder under user files/ (default: BenchmarkFiles)
  --container <name>     Nextcloud container name (default: nextcloud-container)
  --no-scan              Skip occ files:scan after file creation
  --no-tag-unscanned     Skip occ gdatavaas:tag-unscanned after scan
  --help                 Show this help

Examples:
  ./scripts/populate-nextcloud-benchmark-files.sh --count 100000 --jobs 16
  ./scripts/populate-nextcloud-benchmark-files.sh --count 200000 --size-bytes 1024 --dir BenchA
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --count)
      FILE_COUNT="$2"
      shift 2
      ;;
    --size-bytes)
      FILE_SIZE="$2"
      shift 2
      ;;
    --jobs)
      JOBS="$2"
      shift 2
      ;;
    --user)
      NC_USER="$2"
      shift 2
      ;;
    --dir)
      TARGET_DIR="$2"
      shift 2
      ;;
    --container)
      NC_CONTAINER="$2"
      shift 2
      ;;
    --no-scan)
      SCAN_AFTER_CREATE=0
      shift 1
      ;;
    --no-tag-unscanned)
      TAG_UNSCANNED_AFTER_CREATE=0
      shift 1
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

if [[ ! "$FILE_COUNT" =~ ^[0-9]+$ ]] || [[ "$FILE_COUNT" -lt 1 ]]; then
  echo "--count must be a positive integer" >&2
  exit 1
fi
if [[ ! "$FILE_SIZE" =~ ^[0-9]+$ ]] || [[ "$FILE_SIZE" -lt 1 ]]; then
  echo "--size-bytes must be a positive integer" >&2
  exit 1
fi
if [[ ! "$JOBS" =~ ^[0-9]+$ ]] || [[ "$JOBS" -lt 1 ]]; then
  echo "--jobs must be a positive integer" >&2
  exit 1
fi

if ! docker ps --format '{{.Names}}' | grep -qx "$NC_CONTAINER"; then
  echo "Container '$NC_CONTAINER' is not running." >&2
  exit 1
fi

TARGET_PATH="/var/www/html/data/${NC_USER}/files/${TARGET_DIR}"

echo "Creating ${FILE_COUNT} files for user '${NC_USER}' in '${TARGET_DIR}' with ${JOBS} workers..."

docker exec --user www-data -i "$NC_CONTAINER" sh -lc "
set -e
mkdir -p '$TARGET_PATH'
seq 1 '$FILE_COUNT' | xargs -P '$JOBS' -I{} sh -c '
  file=\"$TARGET_PATH/file_{}.bin\"
  # Use deterministic content with fixed size for high write throughput.
  head -c '$FILE_SIZE' /dev/zero > \"\$file\"
'
"

if [[ "$SCAN_AFTER_CREATE" -eq 1 ]]; then
  echo "Running occ files:scan for ${NC_USER}/files/${TARGET_DIR} ..."
  docker exec --user www-data -i "$NC_CONTAINER" php occ files:scan --path="${NC_USER}/files/${TARGET_DIR}"
else
  echo "Skipping occ files:scan (--no-scan)."
fi

if [[ "$TAG_UNSCANNED_AFTER_CREATE" -eq 1 ]]; then
  echo "Running occ gdatavaas:tag-unscanned ..."
  docker exec --user www-data -i "$NC_CONTAINER" php occ gdatavaas:tag-unscanned
else
  echo "Skipping occ gdatavaas:tag-unscanned (--no-tag-unscanned)."
fi

echo "Done. Files are now present and indexed for benchmark runs."
