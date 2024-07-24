#!/usr/bin/env bats

setup_file() {
    source tests/bats/.env-test || return 1
    source .env-local || echo "No .env-local file found."
    mkdir -p $FOLDER_PREFIX/
    curl --output $FOLDER_PREFIX/pup.exe http://amtso.eicar.org/PotentiallyUnwanted.exe
    $DOCKER_EXEC_WITH_USER --env OC_PASS=$TESTUSER_PASSWORD nextcloud-container php occ user:add $TESTUSER --password-from-env || echo "already exists"
    $DOCKER_EXEC_WITH_USER nextcloud-container mkdir -p /var/www/html/data/$TESTUSER/files

    $DOCKER_EXEC_WITH_USER nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"
    BATS_NO_PARALLELIZE_WITHIN_FILE=true
    # this is cache busting
    $DOCKER_EXEC_WITH_USER nextcloud-container php occ files:scan --all
    docker exec nextcloud-container chown -R www-data:www-data /var/www/html/
}

@test "test upload when vaas does not function" {
    $DOCKER_EXEC_WITH_USER nextcloud-container php occ config:app:set gdatavaas clientSecret --value="WRONG_PASSWORD"
    RESULT=$(echo $EICAR_STRING | curl --silent -w "%{http_code}" -u admin:admin -T - http://127.0.0.1/remote.php/dav/files/admin/functionality-sequential.eicar.com.txt)
    $DOCKER_EXEC_WITH_USER nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/functionality-sequential.eicar.com.txt
    
    echo "Actual: $RESULT"
    [[ $RESULT -ge 200 && $RESULT -lt 300 ]]
}

@test "test croned scan for admin files" {
    $DOCKER_EXEC_WITH_USER nextcloud-container php occ config:app:set gdatavaas clientSecret --value="WRONG_PASSWORD"
    echo $EICAR_STRING | curl --silent -w "%{http_code}" -u admin:admin -T - http://127.0.0.1/remote.php/dav/files/admin/admin.functionality-sequential.eicar.com.txt
    curl --silent -w "%{http_code}" -u admin:admin -T $FOLDER_PREFIX/pup.exe http://127.0.0.1/remote.php/dav/files/admin/admin.pup.exe
    echo $CLEAN_STRING | curl --silent -w "%{http_code}" -u admin:admin -T - http://127.0.0.1/remote.php/dav/files/admin/admin.functionality-sequential.clean.txt

    $DOCKER_EXEC_WITH_USER nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"

    # check for unscanned tag
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file admin/files/admin.functionality-sequential.eicar.com.txt | grep "Unscanned") ]]
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file admin/files/admin.functionality-sequential.eicar.com.txt | wc -l ) -eq "1" ]]

    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file admin/files/admin.pup.exe | grep "Unscanned" ) ]]
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file admin/files/admin.pup.exe | wc -l ) -eq "1" ]]

    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file admin/files/admin.functionality-sequential.clean.txt | grep "Unscanned" ) ]]
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file admin/files/admin.functionality-sequential.clean.txt | wc -l ) -eq "1" ]]

    $DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:scan

    # check for tags (only one specific should exist for each file)
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file admin/files/admin.functionality-sequential.eicar.com.txt | grep "Malicious") ]]
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file admin/files/admin.functionality-sequential.eicar.com.txt | wc -l ) -eq "1" ]]

    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file admin/files/admin.pup.exe | grep "Pup" ) ]]
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file admin/files/admin.pup.exe | wc -l ) -eq "1" ]]

    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file admin/files/admin.functionality-sequential.clean.txt | grep "Clean" ) ]]
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file admin/files/admin.functionality-sequential.clean.txt | wc -l ) -eq "1" ]]

    LOGS=$($DOCKER_EXEC_WITH_USER -i nextcloud-container tail -5000 data/nextcloud.log | egrep "admin.functionality-sequential.eicar.com.txt|admin.functionality-sequential.clean.txt|admin.pup.exe" )

    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/admin.functionality-sequential.eicar.com.txt
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/admin.pup.exe
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/admin.functionality-sequential.clean.txt

    [[ $LOGS =~ ^.*admin.functionality-sequential.eicar.com.txt.*Verdict:.*Malicious ]]
    [[ $LOGS =~ ^.*admin.pup.exe.*Verdict:.*Pup ]]
    [[ $LOGS =~ ^.*admin.functionality-sequential.clean.txt.*Verdict:.*Clean ]]
}

@test "test croned scan for testuser files" {
    $DOCKER_EXEC_WITH_USER -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="WRONG_PASSWORD"
    
    echo $EICAR_STRING |curl --silent -w "%{http_code}" -u $TESTUSER:$TESTUSER_PASSWORD -T - http://127.0.0.1/remote.php/dav/files/$TESTUSER/$TESTUSER.functionality-sequential.eicar.com.txt
    curl --silent -w "%{http_code}" -u $TESTUSER:$TESTUSER_PASSWORD -T $FOLDER_PREFIX/pup.exe http://127.0.0.1/remote.php/dav/files/$TESTUSER/$TESTUSER.pup.exe
    echo $CLEAN_STRING |curl --silent -w "%{http_code}" -u $TESTUSER:$TESTUSER_PASSWORD -T - http://127.0.0.1/remote.php/dav/files/$TESTUSER/$TESTUSER.functionality-sequential.clean.txt

    $DOCKER_EXEC_WITH_USER -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"

    # check for unscanned tag
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.functionality-sequential.eicar.com.txt | grep "Unscanned") ]]
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.functionality-sequential.eicar.com.txt | wc -l ) -eq "1" ]]

    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.pup.exe | grep "Unscanned" ) ]]
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.pup.exe | wc -l ) -eq "1" ]]

    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.functionality-sequential.clean.txt | grep "Unscanned" ) ]]
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.functionality-sequential.clean.txt | wc -l ) -eq "1" ]]

    $DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:scan

    # check for tags (only one specific should exist for each file)
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.functionality-sequential.eicar.com.txt | grep "Malicious") ]]
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.functionality-sequential.eicar.com.txt | wc -l ) -eq "1" ]]

    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.pup.exe | grep "Pup" ) ]]
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.pup.exe | wc -l ) -eq "1" ]]

    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.functionality-sequential.clean.txt | grep "Clean" ) ]]
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.functionality-sequential.clean.txt | wc -l ) -eq "1" ]]

    LOGS=$($DOCKER_EXEC_WITH_USER -i nextcloud-container tail -5000 data/nextcloud.log | egrep "$TESTUSER.functionality-sequential.eicar.com.txt|$TESTUSER.functionality-sequential.clean.txt|$TESTUSER.pup.exe")

    curl --silent -q -u $TESTUSER:$TESTUSER_PASSWORD -X DELETE http://127.0.0.1/remote.php/dav/files/$TESTUSER/$TESTUSER.functionality-sequential.eicar.com.txt
    curl --silent -q -u $TESTUSER:$TESTUSER_PASSWORD -X DELETE http://127.0.0.1/remote.php/dav/files/$TESTUSER/$TESTUSER.pup.exe
    curl --silent -q -u $TESTUSER:$TESTUSER_PASSWORD -X DELETE http://127.0.0.1/remote.php/dav/files/$TESTUSER/$TESTUSER.functionality-sequential.clean.txt

    # check for scans
    [[ $LOGS =~ ^.*$TESTUSER.functionality-sequential.eicar.com.txt.*Verdict:.*Malicious ]]
    [[ $LOGS =~ ^.*$TESTUSER.pup.exe.*Verdict:.*Pup ]]
    [[ $LOGS =~ ^.*$TESTUSER.functionality-sequential.clean.txt.*Verdict:.*Clean ]]
}

@test "test when unscanned tag is deactivated" {
    $DOCKER_EXEC_WITH_USER -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="WRONG_PASSWORD"
    $DOCKER_EXEC_WITH_USER -i nextcloud-container php occ config:app:set gdatavaas disableUnscannedTag --value="true"
    
    echo $EICAR_STRING |curl --silent -w "%{http_code}" -u $TESTUSER:$TESTUSER_PASSWORD -T - http://127.0.0.1/remote.php/dav/files/$TESTUSER/$TESTUSER.functionality-sequential.eicar.com.txt
    echo $CLEAN_STRING |curl --silent -w "%{http_code}" -u $TESTUSER:$TESTUSER_PASSWORD -T - http://127.0.0.1/remote.php/dav/files/$TESTUSER/$TESTUSER.functionality-sequential.clean.txt

    $DOCKER_EXEC_WITH_USER -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"

    # check for unscanned tag
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.functionality-sequential.eicar.com.txt | grep "Unscanned" | wc -l) -eq "0" ]]
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.functionality-sequential.clean.txt | grep "Unscanned" | wc -l ) -eq "0" ]]

    $DOCKER_EXEC_WITH_USER -i nextcloud-container php occ config:app:set gdatavaas disableUnscannedTag --value="false"

    curl --silent -q -u $TESTUSER:$TESTUSER_PASSWORD -X DELETE http://127.0.0.1/remote.php/dav/files/$TESTUSER/$TESTUSER.functionality-sequential.eicar.com.txt
    curl --silent -q -u $TESTUSER:$TESTUSER_PASSWORD -X DELETE http://127.0.0.1/remote.php/dav/files/$TESTUSER/$TESTUSER.functionality-sequential.clean.txt
}

@test "test mailing on eicar upload" {
    echo $EICAR_STRING | curl --silent -w "%{http_code}" -u admin:admin -T - http://127.0.0.1/remote.php/dav/files/admin/functionality-sequential.eicar.com.txt
    sleep 1

    RESULT=$(curl -X 'GET' 'http://127.0.0.1:8081/api/Messages/new?mailboxName=Default&pageSize=1' -H 'accept: application/json')

    echo $RESULT
    [[ $RESULT =~ "Infected file upload" ]]
    
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/functionality-sequential.eicar.com.txt  
}

tearddown_file() {
    sleep 2   
    rm -rf $FOLDER_PREFIX/
}