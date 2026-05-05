#!/bin/bash

set -euo pipefail

if command -v dockerd >/dev/null 2>&1; then
    mkdir -p /var/log

    if ! docker info >/dev/null 2>&1; then
        dockerd > /var/log/dockerd.log 2>&1 &

        for _ in $(seq 1 30); do
            if docker info >/dev/null 2>&1; then
                break
            fi
            sleep 1
        done

        if ! docker info >/dev/null 2>&1; then
            cat /var/log/dockerd.log >&2 || true
            exit 1
        fi
    fi
fi

exec "$@"