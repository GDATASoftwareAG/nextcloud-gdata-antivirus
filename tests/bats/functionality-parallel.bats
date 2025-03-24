#!/usr/bin/env bats

setup_file() {
    source tests/bats/.env-test || return 1
    source .env-local || echo "No .env-local file found."
    mkdir -p $FOLDER_PREFIX
    curl --output $FOLDER_PREFIX/pup.exe http://amtso.eicar.org/PotentiallyUnwanted.exe
    $DOCKER_EXEC_WITH_USER --env OC_PASS=$TESTUSER_PASSWORD nextcloud-container php occ user:add $TESTUSER --password-from-env || echo "already exists"
    $DOCKER_EXEC_WITH_USER nextcloud-container mkdir -p /var/www/html/data/$TESTUSER/files
    $DOCKER_EXEC_WITH_USER nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"
    
    # this is cache busting
    $DOCKER_EXEC_WITH_USER nextcloud-container php occ files:scan --all
    sleep 2
}

@test "test admin eicar Upload" {
    EICAR_LENGTH=$(echo $EICAR_STRING | wc -c)
    RESULT=$(echo $EICAR_STRING | curl -v -X PUT -d"$EICAR_STRING" -w "%{http_code}" -u admin:admin -T - http://$HOSTNAME/remote.php/dav/files/admin/functionality-parallel.eicar.com.txt || echo "curl failed")

    if [[ "$RESULT" =~ "curl failed" ]]; then
        echo "debugging stuff"
        docker exec -i nextcloud-container ls -lha /tmp/apache2-coredump
        mkdir -p ./coredumps
        docker container cp nextcloud-container:/tmp/apache2-coredump/* ./coredumps
        ls -lha ./coredumps
        $DOCKER_EXEC_WITH_USER -i nextcloud-container ls -lha data
        $DOCKER_EXEC_WITH_USER -i nextcloud-container ls -lha data/admin
        $DOCKER_EXEC_WITH_USER -i nextcloud-container ls -lha data/admin/files
        $DOCKER_EXEC_WITH_USER -i nextcloud-container cat .htaccess
        df -h
        free
        mpstat
        docker stats --no-stream --no-trunc
        $DOCKER_EXEC_WITH_USER -i nextcloud-container cat data/nextcloud.log
        $DOCKER_EXEC_WITH_USER -i nextcloud-container cat /var/www/html/data/php.log
        docker logs nextcloud-container
    fi

    echo "Actual: $RESULT"
    curl --silent -q -u admin:admin -X DELETE http://$HOSTNAME/remote.php/dav/files/admin/functionality-parallel.eicar.com.txt || echo "file not found"
    [[ "$RESULT" =~ "Virus found" ]]
}

@test "test admin clean upload" {
    RESULT=$(echo $CLEAN_STRING | curl -w "%{http_code}" -u admin:admin -T - http://$HOSTNAME/remote.php/dav/files/admin/functionality-parallel.clean.txt || echo "curl failed")

    if [[ "$RESULT" =~ "curl failed" ]]; then
        echo "debugging stuff"
        $DOCKER_EXEC_WITH_USER -i nextcloud-container ls -lha data
        $DOCKER_EXEC_WITH_USER -i nextcloud-container ls -lha data/admin
        $DOCKER_EXEC_WITH_USER -i nextcloud-container ls -lha data/admin/files
        $DOCKER_EXEC_WITH_USER -i nextcloud-container cat .htaccess
        df -h
        free
        mpstat
        docker stats --no-stream --no-trunc
        $DOCKER_EXEC_WITH_USER -i nextcloud-container cat data/nextcloud.log
        $DOCKER_EXEC_WITH_USER -i nextcloud-container cat /var/www/html/data/php.log
        docker logs nextcloud-container
    fi

    echo "Actual: $RESULT"
    curl --silent -q -u admin:admin -X DELETE http://$HOSTNAME/remote.php/dav/files/admin/functionality-parallel.clean.txt || echo "file not found"
    [[ $RESULT -ge 200 && $RESULT -lt 300 ]]
}

@test "test admin pup Upload" {
    RESULT=$(curl --silent -w "%{http_code}" -u admin:admin -T $FOLDER_PREFIX/pup.exe http://$HOSTNAME/remote.php/dav/files/admin/functionality-parallel.pup.exe)
    echo "Actual: $RESULT"
    curl --silent -q -u admin:admin -X DELETE http://$HOSTNAME/remote.php/dav/files/admin/functionality-parallel.pup.exe || echo "file not found"
    [[ $RESULT -ge 200 && $RESULT -lt 300 ]] 
}

@test "test testuser eicar Upload" {
    RESULT=$(echo $EICAR_STRING | curl --silent -w "%{http_code}" -u $TESTUSER:$TESTUSER_PASSWORD -T - http://$HOSTNAME/remote.php/dav/files/$TESTUSER/functionality-parallel.eicar.com.txt)
    echo "Actual: $RESULT"
    $DOCKER_EXEC_WITH_USER -i nextcloud-container php occ config:app:get gdatavaas clientSecret
    curl --silent -q -u $TESTUSER:$TESTUSER_PASSWORD -X DELETE http://$HOSTNAME/remote.php/dav/files/$TESTUSER/functionality-parallel.eicar.com.txt || echo "file not found"
    [[ "$RESULT" =~ "Virus found" ]]
}

@test "test testuser clean Upload" {
    STATUS_CODE=$(echo $CLEAN_STRING | curl --silent -w "%{http_code}" -u $TESTUSER:$TESTUSER_PASSWORD -T - http://$HOSTNAME/remote.php/dav/files/$TESTUSER/functionality-parallel.clean.txt)
    echo "Actual: $RESULT"
    curl --silent -q -u $TESTUSER:$TESTUSER_PASSWORD -X DELETE http://$HOSTNAME/remote.php/dav/files/$TESTUSER/functionality-parallel.clean.txt || echo "file not found"
    [[ $STATUS_CODE -ge 200 && $STATUS_CODE -lt 300 ]] || exit 1
}

@test "test testuser pup Upload" {
    RESULT=$(curl --silent -w "%{http_code}" -u $TESTUSER:$TESTUSER_PASSWORD -T $FOLDER_PREFIX/pup.exe http://$HOSTNAME/remote.php/dav/files/$TESTUSER/functionality-parallel.pup.exe)
    echo "Actual: $RESULT"
    curl --silent -q -u $TESTUSER:$TESTUSER_PASSWORD -X DELETE http://$HOSTNAME/remote.php/dav/files/$TESTUSER/functionality-parallel.pup.exe || echo "file not found"
    [[ $RESULT -ge 200 && $RESULT -lt 300 ]] || exit 1
}

@test "test wontscan tag for testuser" {
    dd if=/dev/zero of=$FOLDER_PREFIX/too-large.dat  bs=1083741824  count=1

    docker cp $FOLDER_PREFIX/too-large.dat nextcloud-container:/var/www/html/data/$TESTUSER/files/$TESTUSER.too-large.dat
    docker exec -i nextcloud-container chown www-data:www-data /var/www/html/data/$TESTUSER/files/$TESTUSER.too-large.dat
    $DOCKER_EXEC_WITH_USER nextcloud-container php occ files:scan --all
    $DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:scan

    $DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.too-large.dat
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.too-large.dat | grep "Won't scan") ]]
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.too-large.dat | wc -l ) -eq "1" ]]

    $DOCKER_EXEC_WITH_USER nextcloud-container rm /var/www/html/data/$TESTUSER/files/$TESTUSER.too-large.dat
}

@test "test unscanned job for admin" {
    docker cp $FOLDER_PREFIX/pup.exe nextcloud-container:/var/www/html/data/admin/files/admin.unscanned.pup.exe
    docker exec -i nextcloud-container chown www-data:www-data /var/www/html/data/admin/files/admin.unscanned.pup.exe
    $DOCKER_EXEC_WITH_USER nextcloud-container php occ files:scan admin
    $DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:tag-unscanned

    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file admin/files/admin.unscanned.pup.exe | grep "Unscanned") ]]
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file admin/files/admin.unscanned.pup.exe | wc -l ) -eq "1" ]]
    
    $DOCKER_EXEC_WITH_USER nextcloud-container rm /var/www/html/data/admin/files/admin.unscanned.pup.exe
}

@test "test unscanned job for testuser" {
    docker cp $FOLDER_PREFIX/pup.exe nextcloud-container:/var/www/html/data/$TESTUSER/files/$TESTUSER.unscanned.pup.exe
    docker exec -i nextcloud-container chown www-data:www-data /var/www/html/data/$TESTUSER/files/$TESTUSER.unscanned.pup.exe
    $DOCKER_EXEC_WITH_USER nextcloud-container php occ files:scan $TESTUSER
    $DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:tag-unscanned

    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.unscanned.pup.exe | grep "Unscanned") ]]
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.unscanned.pup.exe | wc -l ) -eq "1" ]]

    $DOCKER_EXEC_WITH_USER nextcloud-container rm /var/www/html/data/$TESTUSER/files/$TESTUSER.unscanned.pup.exe
}


@tearddown_file() {
    rm -rf $FOLDER_PREFIX/
}
 