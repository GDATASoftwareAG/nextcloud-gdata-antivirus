#!/usr/bin/env bats

setup_file() {
    mkdir -p ./tmp/functionality-sequential/
    echo 'nothingwronghere' > ./tmp/functionality-sequential/clean.txt
    echo 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*' > ./tmp/functionality-sequential//eicar.com.txt
    curl --output ./tmp/functionality-sequential/pup.exe http://amtso.eicar.org/PotentiallyUnwanted.exe
    BATS_NO_PARALLELIZE_WITHIN_FILE=true
}


@test "test upload when vaas does not function" {
    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="WRONG_PASSWORD"
    RESULT=$(curl --silent -w "%{http_code}" -u admin:admin -T ./tmp/functionality-sequential/eicar.com.txt http://127.0.0.1/remote.php/dav/files/admin/functionality-sequential.eicar.com.txt)
    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/functionality-sequential.eicar.com.txt
    
    echo "Actual: $RESULT"
    [[ $RESULT -ge 200 && $RESULT -lt 300 ]]
}

tearddown_file() {
    sleep 2   
    rm -rf ./tmp/functionality-sequential/
}