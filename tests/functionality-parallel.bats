#!/usr/bin/env bats

FOLDER_PREFIX=./tmp/functionality-parallel
TESTUSER=testuser
TESTUSER_PASSWORD=myfancysecurepassword234
EICAR_STRING='X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*'
CLEAN_STRING='nothingwronghere'

setup_file() {
    mkdir -p $FOLDER_PREFIX
    curl --output $FOLDER_PREFIX/pup.exe http://amtso.eicar.org/PotentiallyUnwanted.exe
    docker exec --env OC_PASS=$TESTUSER_PASSWORD --user www-data nextcloud-container php occ user:add $TESTUSER --password-from-env || echo "already exists"
    docker exec -u www-data -i nextcloud-container mkdir -p /var/www/html/data/$TESTUSER/files

    docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"
    sleep 2
}

@test "test admin eicar Upload" {
    RESULT=$(echo $EICAR_STRING | curl --silent -w "%{http_code}" -u admin:admin -T - http://127.0.0.1/remote.php/dav/files/admin/functionality-parallel.eicar.com.txt)
    echo "Actual: $RESULT"
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/functionality-parallel.eicar.com.txt || echo "file not found"
    [[ "$RESULT" =~ "Upload cannot be completed." ]]
}

@test "test admin clean upload" {
    RESULT=$(echo $CLEAN_STRING | curl -w "%{http_code}" -u admin:admin -T - http://127.0.0.1/remote.php/dav/files/admin/functionality-parallel.clean.txt)
    echo "Actual: $RESULT"
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/functionality-parallel.clean.txt || echo "file not found"
    [[ $RESULT -ge 200 && $RESULT -lt 300 ]]
}

@test "test admin pup Upload" {
    RESULT=$(curl --silent -w "%{http_code}" -u admin:admin -T $FOLDER_PREFIX/pup.exe http://127.0.0.1/remote.php/dav/files/admin/functionality-parallel.pup.exe)
    echo "Actual: $RESULT"
    curl --silent -q -u admin:admin -X DELETE http://127.0.0.1/remote.php/dav/files/admin/functionality-parallel.pup.exe || echo "file not found"
    [[ $RESULT -ge 200 && $RESULT -lt 300 ]] 
}

@test "test testuser eicar Upload" {
    RESULT=$(echo $EICAR_STRING | curl --silent -w "%{http_code}" -u $TESTUSER:$TESTUSER_PASSWORD -T - http://127.0.0.1/remote.php/dav/files/$TESTUSER/functionality-parallel.eicar.com.txt)
    echo "Actual: $RESULT"
    docker exec --user www-data -i nextcloud-container php occ config:app:get gdatavaas clientSecret
    curl --silent -q -u $TESTUSER:$TESTUSER_PASSWORD -X DELETE http://127.0.0.1/remote.php/dav/files/$TESTUSER/functionality-parallel.eicar.com.txt || echo "file not found"
    [[ "$RESULT" =~ "Upload cannot be completed." ]]
}

@test "test testuser clean Upload" {
    STATUS_CODE=$(echo $CLEAN_STRING | curl --silent -w "%{http_code}" -u $TESTUSER:$TESTUSER_PASSWORD -T - http://127.0.0.1/remote.php/dav/files/$TESTUSER/functionality-parallel.clean.txt)
    echo "Actual: $RESULT"
    curl --silent -q -u $TESTUSER:$TESTUSER_PASSWORD -X DELETE http://127.0.0.1/remote.php/dav/files/$TESTUSER/functionality-parallel.clean.txt || echo "file not found"
    [[ $STATUS_CODE -ge 200 && $STATUS_CODE -lt 300 ]] || exit 1
}

@test "test testuser pup Upload" {
    RESULT=$(curl --silent -w "%{http_code}" -u $TESTUSER:$TESTUSER_PASSWORD -T $FOLDER_PREFIX/pup.exe http://127.0.0.1/remote.php/dav/files/$TESTUSER/functionality-parallel.pup.exe)
    echo "Actual: $RESULT"
    curl --silent -q -u $TESTUSER:$TESTUSER_PASSWORD -X DELETE http://127.0.0.1/remote.php/dav/files/$TESTUSER/functionality-parallel.pup.exe || echo "file not found"
    [[ $RESULT -ge 200 && $RESULT -lt 300 ]] || exit 1
}

@test "test unscanned job for admin" {
    docker cp $FOLDER_PREFIX/pup.exe nextcloud-container:/var/www/html/data/admin/files/admin.unscanned.pup.exe
    docker exec -i nextcloud-container chown www-data:www-data /var/www/html/data/admin/files/admin.unscanned.pup.exe
    docker exec -i --user www-data nextcloud-container php occ files:scan --all
    docker exec -i --user www-data nextcloud-container php occ gdatavaas:tag-unscanned

    [[ $(docker exec -i --user www-data nextcloud-container php occ gdatavaas:get-tags-for-file admin/files/admin.unscanned.pup.exe | grep "Unscanned") ]]
    [[ $(docker exec -i --user www-data nextcloud-container php occ gdatavaas:get-tags-for-file admin/files/admin.unscanned.pup.exe | wc -l ) -eq "1" ]]
    
    docker exec -i --user www-data nextcloud-container rm /var/www/html/data/admin/files/admin.unscanned.pup.exe
}

@test "test unscanned job for testuser" {
    docker cp $FOLDER_PREFIX/pup.exe nextcloud-container:/var/www/html/data/$TESTUSER/files/$TESTUSER.unscanned.pup.exe
    docker exec -i nextcloud-container chown www-data:www-data /var/www/html/data/$TESTUSER/files/$TESTUSER.unscanned.pup.exe
    docker exec -i --user www-data nextcloud-container php occ files:scan --all
    docker exec -i --user www-data nextcloud-container php occ gdatavaas:tag-unscanned

    [[ $(docker exec -i --user www-data nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.unscanned.pup.exe | grep "Unscanned") ]]
    [[ $(docker exec -i --user www-data nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.unscanned.pup.exe | wc -l ) -eq "1" ]]

    docker exec -i --user www-data nextcloud-container rm /var/www/html/data/$TESTUSER/files/$TESTUSER.unscanned.pup.exe
}

@test "test wontscan tag for testuser" {
    dd if=/dev/zero of=$FOLDER_PREFIX/too-large.dat  bs=268435457  count=1

    docker cp $FOLDER_PREFIX/too-large.dat nextcloud-container:/var/www/html/data/$TESTUSER/files/$TESTUSER.too-large.dat
    docker exec -i nextcloud-container chown www-data:www-data /var/www/html/data/$TESTUSER/files/$TESTUSER.too-large.dat
    docker exec -i --user www-data nextcloud-container php occ files:scan --all
    docker exec -i --user www-data nextcloud-container php occ gdatavaas:tag-unscanned

    docker exec -i --user www-data nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.too-large.dat
    [[ $(docker exec -i --user www-data nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.too-large.dat | grep "Won't scan") ]]
    [[ $(docker exec -i --user www-data nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.too-large.dat | wc -l ) -eq "1" ]]

    docker exec -i --user www-data nextcloud-container rm /var/www/html/data/$TESTUSER/files/$TESTUSER.too-large.dat
}

@tearddown_file() {
    rm -rf $FOLDER_PREFIX/
}
 