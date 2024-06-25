#!/bin/bash

foobar() {
    sleep 3
    echo "foobar"
    exit 123
}

foobar2() {
    sleep 2
    echo "foobar2"
    exit 123
}

foobar &
foobar2 &

wait %1 %2 || echo "failed"
