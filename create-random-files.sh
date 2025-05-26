#!/bin/bash

for i in $(seq 1 1000); do
  tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 10 > "/var/www/html/data/admin/files/RandomFiles/file_$i.txt"
done
