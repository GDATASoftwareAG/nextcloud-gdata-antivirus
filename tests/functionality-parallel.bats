#!/usr/bin/env bats

setup_file() {
    mkdir -p ./tmp/functionality-parallel/
    echo 'nothingwronghere' > ./tmp/functionality-parallel//clean.txt
    echo 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*' > ./tmp/functionality-parallel/eicar.com.txt
    curl --output ./tmp/functionality-parallel/pup.exe http://amtso.eicar.org/PotentiallyUnwanted.exe
    docker exec --env OC_PASS=myfancysecurepassword234 --user www-data nextcloud-container php occ user:add testuser --password-from-env || echo "already exists"
    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"
    sleep 2
}

@test "test admin eicar Upload" {
    RESULT=$(curl --silent -w "%{http_code}" -u admin:admin -T ./tmp/functionality-parallel/eicar.com.txt http://127.0.0.1/remote.php/dav/files/admin/functionality-parallel.eicar.com.txt)
    echo "Actual: $RESULT"
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/functionality-parallel.eicar.com.txt
    [[ "$RESULT" =~ "Virus EICAR-Test-File is detected in the file. Upload cannot be completed." ]]
}

@test "test admin clean Upload" {
    RESULT=$(curl -w "%{http_code}" -u admin:admin -T ./tmp/functionality-parallel/clean.txt http://127.0.0.1/remote.php/dav/files/admin/functionality-parallel.clean.txt)
    echo "Actual: $RESULT"
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/functionality-parallel.clean.txt
    [[ $RESULT -ge 200 && $RESULT -lt 300 ]]
}

@test "test admin pup Upload" {
    RESULT=$(curl --silent -w "%{http_code}" -u admin:admin -T ./tmp/functionality-parallel/pup.exe http://127.0.0.1/remote.php/dav/files/admin/functionality-parallel.pup.exe)
    echo "Actual: $RESULT"
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/functionality-parallel.pup.exe
    [[ $RESULT -ge 200 && $RESULT -lt 300 ]] 
}

@test "test testuser eicar Upload" {
    RESULT=$(curl --silent -w "%{http_code}" -u testuser:myfancysecurepassword234 -T ./tmp/functionality-parallel/eicar.com.txt http://127.0.0.1/remote.php/dav/files/testuser/functionality-parallel.eicar.com.txt)
    echo "Actual: $RESULT"
    curl --silent -q -u testuser:myfancysecurepassword234 -X DELETE http://127.0.0.1/remote.php/dav/files/testuser/functionality-parallel.eicar.com.txt
    [[ "$RESULT" =~ "Virus EICAR-Test-File is detected in the file. Upload cannot be completed." ]]
}

@test "test testuser clean Upload" {
    STATUS_CODE=$(curl --silent -w "%{http_code}" -w "%{http_code}" -u testuser:myfancysecurepassword234 -T ./tmp/functionality-parallel/clean.txt http://127.0.0.1/remote.php/dav/files/testuser/functionality-parallel.clean.txt)
    echo "Actual: $RESULT"
    curl --silent -q -u testuser:myfancysecurepassword234 -X DELETE http://127.0.0.1/remote.php/dav/files/testuser/functionality-parallel.clean.txt
    [[ $STATUS_CODE -ge 200 && $STATUS_CODE -lt 300 ]] || exit 1
}

@test "test testuser pup Upload" {
   RESULT=$(curl --silent -w "%{http_code}" -w "%{http_code}" -u testuser:myfancysecurepassword234 -T ./tmp/functionality-parallel/pup.exe http://127.0.0.1/remote.php/dav/files/testuser/functionality-parallel.pup.exe)
    echo "Actual: $RESULT"
    curl --silent -q -u testuser:myfancysecurepassword234 -X DELETE http://127.0.0.1/remote.php/dav/files/testuser/functionality-parallel.pup.exe
    [[ $RESULT -ge 200 && $RESULT -lt 300 ]] || exit 1
}

@tearddown_file() {
    rm -rf ./tmp/functionality-parallel/
}
 