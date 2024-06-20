#!/bin/bash

MIN_VERSION=$([[ $(cat appinfo/info.xml) =~ $(echo 'min-version=[^[0-9]]*([0-9]+).*') ]] && echo ${BASH_REMATCH[1]})
MAX_VERSION=$([[ $(cat appinfo/info.xml) =~ $(echo 'max-version=[^[0-9]]*([0-9]+).*') ]] && echo ${BASH_REMATCH[1]})

BASE_TEMPLATE='[VERSIONS]'
VERSION_TEMPLATE='"NEXTCLOUD_VERSION"'

if [ -z "$MIN_VERSION" ] && [ -z "$MAX_VERSION" ]; then
    echo '["29"]'
    exit 0
fi

if [ -z "$MIN_VERSION" ] && [ -n "$MAX_VERSION" ] ; then
    VERSIONS=$(echo $VERSION_TEMPLATE | sed "s/NEXTCLOUD_VERSION/$MAX_VERSION/g")
    echo $BASE_TEMPLATE | sed "s/VERSIONS/$VERSIONS/g"
    exit 0
fi

if [ -n "$MIN_VERSION" ] && [ -z "$MAX_VERSION" ] ; then
    VERSIONS=$(echo $VERSION_TEMPLATE | sed "s/NEXTCLOUD_VERSION/$MIN_VERSION/g")
    echo $BASE_TEMPLATE | sed "s/VERSIONS/$VERSIONS/g"
fi

if [ "$MIN_VERSION" -eq "$MAX_VERSION" ]; then
    VERSIONS=$(echo $VERSION_TEMPLATE | sed "s/NEXTCLOUD_VERSION/$MIN_VERSION/g")
    echo $BASE_TEMPLATE | sed "s/VERSIONS/$VERSIONS/g"
    exit 0
fi

if [ "$MIN_VERSION" -gt "$MAX_VERSION" ]; then
    echo 'Min version should be less or equal to max version'
    exit 1
fi

VERSIONS=""
DELIMITER=""
for i in $(eval echo {$MIN_VERSION..$MAX_VERSION})
do
    VERSIONS="${VERSIONS}${DELIMITER}$(echo $VERSION_TEMPLATE | sed "s/NEXTCLOUD_VERSION/$i/g")"
    DELIMITER=","
done
echo $BASE_TEMPLATE | sed "s/VERSIONS/$VERSIONS/g"



