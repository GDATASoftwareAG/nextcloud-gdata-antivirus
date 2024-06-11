#!/usr/bin/env bats

setup_file() {
    echo 'nothingwronghere' > /tmp/clean.txt
    echo 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*' > /tmp/eicar.com.txt
    curl --output /tmp/pup.exe http://amtso.eicar.org/PotentiallyUnwanted.exe
    BATS_NO_PARALLELIZE_WITHIN_FILE=true
}

setup() {
    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="WRONG_PASSWORD"
}

@test "test upload when vaas does not function" {
    RESULT=$(curl --silent -w "%{http_code}" -u admin:admin -T /tmp/eicar.com.txt http://127.0.0.1/remote.php/dav/files/admin/eicar.com.txt)
    echo "Actual: $RESULT"
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/eicar.com.txt
    [[ $RESULT -ge 200 && $RESULT -lt 300 ]]
}

tearddown() {
    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"   
}