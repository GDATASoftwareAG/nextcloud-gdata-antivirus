#!/usr/bin/env bats

FOLDER_PREFIX=./tmp/functionality-parallel
TESTUSER=testuser
TESTUSER_PASSWORD=myfancysecurepassword234

setup_file() {
    mkdir -p $FOLDER_PREFIX
    docker exec --env OC_PASS=$TESTUSER_PASSWORD --user www-data nextcloud-container php occ user:add $TESTUSER --password-from-env || echo "already exists"
    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"
    sleep 2
}

setup() {
    echo 'nothingwronghere' > $FOLDER_PREFIX/clean.txt
    echo 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*' > $FOLDER_PREFIX/eicar.com.txt
    curl --output $FOLDER_PREFIX/pup.exe http://amtso.eicar.org/PotentiallyUnwanted.exe
}

@test "test admin eicar Upload" {
    RESULT=$(curl --silent -w "%{http_code}" -u admin:admin -T $FOLDER_PREFIX/eicar.com.txt http://127.0.0.1/remote.php/dav/files/admin/functionality-parallel.eicar.com.txt)
    echo "Actual: $RESULT"
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/functionality-parallel.eicar.com.txt
    [[ "$RESULT" =~ "Upload cannot be completed." ]]
}

@test "test admin clean upload" {
    RESULT=$(curl -w "%{http_code}" -u admin:admin -T $FOLDER_PREFIX/clean.txt http://127.0.0.1/remote.php/dav/files/admin/functionality-parallel.clean.txt)
    echo "Actual: $RESULT"
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/functionality-parallel.clean.txt
    [[ $RESULT -ge 200 && $RESULT -lt 300 ]]
}

@test "test admin pup Upload" {
    RESULT=$(curl --silent -w "%{http_code}" -u admin:admin -T $FOLDER_PREFIX/pup.exe http://127.0.0.1/remote.php/dav/files/admin/functionality-parallel.pup.exe)
    echo "Actual: $RESULT"
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/functionality-parallel.pup.exe
    [[ $RESULT -ge 200 && $RESULT -lt 300 ]] 
}

@test "test testuser eicar Upload" {
    RESULT=$(curl --silent -w "%{http_code}" -u $TESTUSER:$TESTUSER_PASSWORD -T ./tmp/functionality-sequential//eicar.com.txt http://127.0.0.1/remote.php/dav/files/$TESTUSER/functionality-parallel.eicar.com.txt)
    echo "Actual: $RESULT"
    docker exec --user www-data -i nextcloud-container php occ config:app:get gdatavaas clientSecret
    curl --silent -q -u $TESTUSER:$TESTUSER_PASSWORD -X DELETE http://127.0.0.1/remote.php/dav/files/$TESTUSER/functionality-parallel.eicar.com.txt
    [[ "$RESULT" =~ "Upload cannot be completed." ]]
}

@test "test testuser clean Upload" {
    STATUS_CODE=$(curl --silent -w "%{http_code}" -w "%{http_code}" -u $TESTUSER:$TESTUSER_PASSWORD -T $FOLDER_PREFIX/clean.txt http://127.0.0.1/remote.php/dav/files/$TESTUSER/functionality-parallel.clean.txt)
    echo "Actual: $RESULT"
    curl --silent -q -u $TESTUSER:$TESTUSER_PASSWORD -X DELETE http://127.0.0.1/remote.php/dav/files/$TESTUSER/functionality-parallel.clean.txt
    [[ $STATUS_CODE -ge 200 && $STATUS_CODE -lt 300 ]] || exit 1
}

@test "test testuser pup Upload" {
   RESULT=$(curl --silent -w "%{http_code}" -w "%{http_code}" -u $TESTUSER:$TESTUSER_PASSWORD -T $FOLDER_PREFIX/pup.exe http://127.0.0.1/remote.php/dav/files/$TESTUSER/functionality-parallel.pup.exe)
    echo "Actual: $RESULT"
    curl --silent -q -u $TESTUSER:$TESTUSER_PASSWORD -X DELETE http://127.0.0.1/remote.php/dav/files/$TESTUSER/functionality-parallel.pup.exe
    [[ $RESULT -ge 200 && $RESULT -lt 300 ]] || exit 1
}

@tearddown_file() {
    rm -rf $FOLDER_PREFIX/
}
 