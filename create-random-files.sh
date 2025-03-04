#!/bin/bash

for i in $(seq 1 100000); do echo $(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | head -c 10) > /var/www/html/data/admin/files/RandomFiles/file_"$i".txt; done