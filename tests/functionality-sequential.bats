#!/usr/bin/env bats

FOLDER_PREFIX=./tmp/functionality-sequential
TESTUSER=testuser
TESTUSER_PASSWORD=myfancysecurepassword234

setup_file() {
    mkdir -p $FOLDER_PREFIX/
    echo 'nothingwronghere' > $FOLDER_PREFIX/clean.txt
    echo 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*' > $FOLDER_PREFIX/eicar.com.txt
    curl --output $FOLDER_PREFIX/pup.exe http://amtso.eicar.org/PotentiallyUnwanted.exe
    BATS_NO_PARALLELIZE_WITHIN_FILE=true
}


@test "test upload when vaas does not function" {
    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="WRONG_PASSWORD"
    RESULT=$(curl --silent -w "%{http_code}" -u admin:admin -T $FOLDER_PREFIX/eicar.com.txt http://127.0.0.1/remote.php/dav/files/admin/functionality-sequential.eicar.com.txt)
    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/functionality-sequential.eicar.com.txt
    
    echo "Actual: $RESULT"
    [[ $RESULT -ge 200 && $RESULT -lt 300 ]]
}

@test "test croned scan for admin files" {
    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="WRONG_PASSWORD"
    curl --silent -w "%{http_code}" -u admin:admin -T $FOLDER_PREFIX/eicar.com.txt http://127.0.0.1/remote.php/dav/files/admin/admin.functionality-sequential.eicar.com.txt
    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"
    docker exec --user www-data -i nextcloud-container php ./cron.php # tag unscanned
    docker exec --user www-data -i nextcloud-container php ./cron.php # actual scan

    LOGS=$(docker exec --user www-data -i nextcloud-container cat data/nextcloud.log | egrep "admin.functionality-sequential.eicar.com.txt|Readme.md" )

    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/admin.functionality-sequential.eicar.com.txt

    [[ $LOGS =~ ^.*admin.functionality-sequential.eicar.com.txt.*Verdict:.*Malicious ]]
    [[ $LOGS =~ ^.*Readme.md.*Verdict:.*Clean ]]
}

@test "test croned scan for testuser files" {
    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="WRONG_PASSWORD"
    curl --silent -w "%{http_code}" -u $TESTUSER:$TESTUSER_PASSWORD -T $FOLDER_PREFIX/eicar.com.txt http://127.0.0.1/remote.php/dav/files/$TESTUSER/$TESTUSER.functionality-sequential.eicar.com.txt
    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"
    docker exec --user www-data -i nextcloud-container php ./cron.php # tag unscanned
    docker exec --user www-data -i nextcloud-container php ./cron.php # actual scan

    LOGS=$(docker exec --user www-data -i nextcloud-container cat data/nextcloud.log | egrep "$TESTUSER.functionality-sequential.eicar.com.txt")

    curl --silent -q -u $TESTUSER:$TESTUSER_PASSWORD -X DELETE http://127.0.0.1/remote.php/dav/files/$TESTUSER/$TESTUSER.functionality-sequential.eicar.com.txt

    [[ $LOGS =~ ^.*$TESTUSER.functionality-sequential.eicar.com.txt.*Verdict:.*Malicious ]]
}

tearddown_file() {
    sleep 2   
    rm -rf $FOLDER_PREFIX/
}