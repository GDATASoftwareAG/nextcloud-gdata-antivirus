#!/usr/bin/env bats

setup_file() {
    BATS_NO_PARALLELIZE_WITHIN_FILE=true
}

setup() {
    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="WRONG_PASSWORD"
}

@test "test upload when vaas does not function" {
    RESULT=$(curl --silent -w "%{http_code}" -u admin:admin -T /tmp/eicar.com.txt http://127.0.0.1/remote.php/dav/files/admin/eicar.com.txt)
    echo "Actual: $RESULT"
    [[ $RESULT -ge 200 && $RESULT -lt 300 ]] || exit 1
}

tearddown() {
    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"   
}