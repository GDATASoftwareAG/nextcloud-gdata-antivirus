#!/usr/bin/env bats

FOLDER_PREFIX=./tmp/functionality-sequential
TESTUSER=testuser
TESTUSER_PASSWORD=myfancysecurepassword234
EICAR_STRING='X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*'
CLEAN_STRING='nothingwronghere'

setup_file() {
    mkdir -p $FOLDER_PREFIX/
    curl --output $FOLDER_PREFIX/pup.exe http://amtso.eicar.org/PotentiallyUnwanted.exe
    docker exec --env OC_PASS=$TESTUSER_PASSWORD --user www-data nextcloud-container php occ user:add $TESTUSER --password-from-env || echo "already exists"
    BATS_NO_PARALLELIZE_WITHIN_FILE=true
}

@test "test upload when vaas does not function" {
    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="WRONG_PASSWORD"
    RESULT=$(echo $EICAR_STRING | curl --silent -w "%{http_code}" -u admin:admin -T - http://127.0.0.1/remote.php/dav/files/admin/functionality-sequential.eicar.com.txt)
    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/functionality-sequential.eicar.com.txt
    
    echo "Actual: $RESULT"
    [[ $RESULT -ge 200 && $RESULT -lt 300 ]]
}

@test "test croned scan for admin files" {
    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="WRONG_PASSWORD"
    echo $EICAR_STRING | curl --silent -w "%{http_code}" -u admin:admin -T - http://127.0.0.1/remote.php/dav/files/admin/admin.functionality-sequential.eicar.com.txt
    curl --silent -w "%{http_code}" -u admin:admin -T $FOLDER_PREFIX/pup.exe http://127.0.0.1/remote.php/dav/files/admin/admin.pup.exe
    echo $CLEAN_STRING | curl --silent -w "%{http_code}" -u admin:admin -T - http://127.0.0.1/remote.php/dav/files/admin/admin.functionality-sequential.clean.txt

    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"

    docker exec -i --user www-data nextcloud-container php occ gdatavaas:tag-unscanned
    docker exec -i --user www-data nextcloud-container php occ gdatavaas:scan

    LOGS=$(docker exec --user www-data -i nextcloud-container php occ log:tail -nr 5000 | egrep "admin.functionality-sequential.eicar.com.txt|admin.functionality-sequential.clean.txt|admin.pup.exe" )

    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/admin.functionality-sequential.eicar.com.txt
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/admin.pup.exe
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/admin.functionality-sequential.clean.txt

    [[ $LOGS =~ ^.*admin.functionality-sequential.eicar.com.txt.*Verdict:.*Malicious ]]
    [[ $LOGS =~ ^.*admin.pup.exe.*Verdict:.*Pup ]]
    [[ $LOGS =~ ^.*admin.functionality-sequential.clean.txt.*Verdict:.*Clean ]]
}

@test "test croned scan for testuser files" {
    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="WRONG_PASSWORD"
    
    echo $EICAR_STRING |curl --silent -w "%{http_code}" -u $TESTUSER:$TESTUSER_PASSWORD -T - http://127.0.0.1/remote.php/dav/files/$TESTUSER/$TESTUSER.functionality-sequential.eicar.com.txt
    curl --silent -w "%{http_code}" -u $TESTUSER:$TESTUSER_PASSWORD -T $FOLDER_PREFIX/pup.exe http://127.0.0.1/remote.php/dav/files/$TESTUSER/$TESTUSER.pup.exe
    echo $CLEAN_STRING |curl --silent -w "%{http_code}" -u $TESTUSER:$TESTUSER_PASSWORD -T - http://127.0.0.1/remote.php/dav/files/$TESTUSER/$TESTUSER.functionality-sequential.clean.txt

    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"

    docker exec -i --user www-data nextcloud-container php occ gdatavaas:tag-unscanned
    docker exec -i --user www-data nextcloud-container php occ gdatavaas:scan

    LOGS=$(docker exec --user www-data -i nextcloud-container php occ log:tail -nr 5000 | egrep "$TESTUSER.functionality-sequential.eicar.com.txt|$TESTUSER.functionality-sequential.clean.txt|$TESTUSER.pup.exe")

    curl --silent -q -u $TESTUSER:$TESTUSER_PASSWORD -X DELETE http://127.0.0.1/remote.php/dav/files/$TESTUSER/$TESTUSER.functionality-sequential.eicar.com.txt
    curl --silent -q -u $TESTUSER:$TESTUSER_PASSWORD -X DELETE http://127.0.0.1/remote.php/dav/files/$TESTUSER/$TESTUSER.pup.exe
    curl --silent -q -u $TESTUSER:$TESTUSER_PASSWORD -X DELETE http://127.0.0.1/remote.php/dav/files/$TESTUSER/$TESTUSER.functionality-sequential.clean.txt

    [[ $LOGS =~ ^.*$TESTUSER.functionality-sequential.eicar.com.txt.*Verdict:.*Malicious ]]
    [[ $LOGS =~ ^.*$TESTUSER.pup.exe.*Verdict:.*Pup ]]
    [[ $LOGS =~ ^.*$TESTUSER.functionality-sequential.clean.txt.*Verdict:.*Clean ]]

}

tearddown_file() {
    sleep 2   
    rm -rf $FOLDER_PREFIX/
}