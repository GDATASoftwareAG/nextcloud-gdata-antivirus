#!/usr/bin/env bats


setup_file() {
    echo 'nothingwronghere' > /tmp/clean.txt
    echo 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*' > /tmp/eicar.com.txt
    curl --output /tmp/pup.exe http://amtso.eicar.org/PotentiallyUnwanted.exe
    docker exec --env OC_PASS=myfancysecurepassword234 --user www-data nextcloud-container php occ user:add testuser --password-from-env || echo "already exists"
}

@test "test admin eicar Upload" {
    RESULT=$(curl --silent -u admin:admin -T /tmp/eicar.com.txt http://127.0.0.1/remote.php/dav/files/admin/eicar.com.txt)
    [[ "$RESULT" =~ "Virus EICAR-Test-File is detected in the file. Upload cannot be completed." ]]
}

@test "test admin clean Upload" {
    STATUS_CODE=$(curl --silent -w "%{http_code}" -u admin:admin -T /tmp/clean.txt http://127.0.0.1/remote.php/dav/files/admin/clean.txt)
    [[ $STATUS_CODE -ge 200 && $STATUS_CODE -lt 300 ]]
}

@test "test admin pup Upload" {
    STATUS_CODE=$(curl --silent -w "%{http_code}" -u admin:admin -T /tmp/pup.exe http://127.0.0.1/remote.php/dav/files/admin/pup.exe)
    [[ $STATUS_CODE -ge 200 && $STATUS_CODE -lt 300 ]] 
}

@test "test testuser eicar Upload" {
    RESULT=$(curl --silent -u testuser:myfancysecurepassword234 -T /tmp/eicar.com.txt http://127.0.0.1/remote.php/dav/files/testuser/eicar.com.txt)
    [[ "$RESULT" =~ "Virus EICAR-Test-File is detected in the file. Upload cannot be completed." ]]
}

@test "test testuser clean Upload" {
    STATUS_CODE=$(curl --silent -w "%{http_code}" -u testuser:myfancysecurepassword234 -T /tmp/clean.txt http://127.0.0.1/remote.php/dav/files/testuser/clean.txt)
    [[ $STATUS_CODE -ge 200 && $STATUS_CODE -lt 300 ]] || exit 1
}

@test "test testuser pup Upload" {
   STATUS_CODE=$(curl --silent -w "%{http_code}" -u testuser:myfancysecurepassword234 -T /tmp/pup.exe http://127.0.0.1/remote.php/dav/files/testuser/pup.exe)
    [[ $STATUS_CODE -ge 200 && $STATUS_CODE -lt 300 ]] || exit 1
}
 