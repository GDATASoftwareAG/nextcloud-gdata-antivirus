#!/usr/bin/env bats

# SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
#
# SPDX-License-Identifier: AGPL-3.0-or-later

setup_file() {
    source tests/bats/.env-test || return 1
    source .env-local || source .env || echo "No .env files found."
    mkdir -p $FOLDER_PREFIX
    curl --output $FOLDER_PREFIX/pup.exe http://amtso.eicar.org/PotentiallyUnwanted.exe
    $DOCKER_EXEC_WITH_USER --env OC_PASS=$TESTUSER_PASSWORD nextcloud-container php occ user:add $TESTUSER --password-from-env || echo "already exists"
    $DOCKER_EXEC_WITH_USER nextcloud-container mkdir -p /var/www/html/data/$TESTUSER/files
    $DOCKER_EXEC_WITH_USER nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"

    # this is cache busting
    $DOCKER_EXEC_WITH_USER nextcloud-container php occ files:scan --all
    sleep 2
    $DOCKER_EXEC_WITH_USER nextcloud-container php occ app:enable gdatavaas
}

@test "test admin eicar Upload" {
    EICAR_LENGTH=$(echo $EICAR_STRING | wc -c)
    RESULT=$(echo $EICAR_STRING | curl -v -X PUT -d"$EICAR_STRING" -w "%{http_code}" -u admin:admin -T - http://$HOSTNAME/remote.php/dav/files/admin/functionality-parallel.eicar.com.txt || echo "curl failed")

    echo "Actual: $RESULT"
    curl --silent -q -u admin:admin -X DELETE http://$HOSTNAME/remote.php/dav/files/admin/functionality-parallel.eicar.com.txt || echo "file not found"
    [[ "$RESULT" =~ "Virus found" ]]
}

@test "test admin clean upload" {
    RESULT=$(echo $CLEAN_STRING | curl -w "%{http_code}" -u admin:admin -T - http://$HOSTNAME/remote.php/dav/files/admin/functionality-parallel.clean.txt || echo "curl failed")

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
    dd if=/dev/zero of=$FOLDER_PREFIX/too-large.dat  bs=268435457  count=1

    docker cp $FOLDER_PREFIX/too-large.dat nextcloud-container:/var/www/html/data/$TESTUSER/files/$TESTUSER.too-large.dat
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
    $DOCKER_EXEC_WITH_USER nextcloud-container php occ files:scan $TESTUSER
    $DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:tag-unscanned

    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.unscanned.pup.exe | grep "Unscanned") ]]
    [[ $($DOCKER_EXEC_WITH_USER nextcloud-container php occ gdatavaas:get-tags-for-file $TESTUSER/files/$TESTUSER.unscanned.pup.exe | wc -l ) -eq "1" ]]

    $DOCKER_EXEC_WITH_USER nextcloud-container rm /var/www/html/data/$TESTUSER/files/$TESTUSER.unscanned.pup.exe
}


# Controller endpoint tests using admin user

# Helper function for testing GET endpoints
test_get_endpoint() {
    local endpoint="$1"
    local description="$2"
    local expected_http_status="${3:-200}"  # Default to 200 if not provided
    local user_name="${4:-admin}"  # Default to admin if not provided
    local password="${5:-admin}"   # Default to admin if not provided
    
    RESULT=$(curl --silent -w "%{http_code}" -u "$user_name:$password" -X GET \
        http://$HOSTNAME/apps/gdatavaas/$endpoint)
    
    echo "$description result: $RESULT"
    [[ $RESULT -eq $expected_http_status ]]
}

# Helper function for testing POST endpoints
test_post_endpoint() {
    local endpoint="$1"
    local data="$2"
    local description="$3"
    local expected_http_status="${4:-200}"  # Default to 200 if not provided
    local user_name="${5:-admin}"  # Default to admin if not provided
    local password="${6:-admin}"   # Default to admin if not provided
    
    RESULT=$(curl --silent -w "%{http_code}" -u "$user_name:$password" -X POST \
        -H "Content-Type: application/json" \
        -d "$data" \
        http://$HOSTNAME/apps/gdatavaas/$endpoint)
    
    echo "$description result: $RESULT"
    [[ $RESULT -eq $expected_http_status ]]
}

@test "test scan endpoint (POST /scan)" {
    # Create a test file first
    echo $CLEAN_STRING > $FOLDER_PREFIX/test-scan.txt
    docker cp $FOLDER_PREFIX/test-scan.txt nextcloud-container:/var/www/html/data/admin/files/test-scan.txt
    $DOCKER_EXEC_WITH_USER nextcloud-container php occ files:scan admin
    
    # Test scan endpoint
    test_post_endpoint "scan" '{"path":"test-scan.txt"}' "Scan endpoint" "200"
    
    $DOCKER_EXEC_WITH_USER nextcloud-container rm -f /var/www/html/data/admin/files/test-scan.txt
}



# Parameterized tests for GET endpoints
@test "test GET endpoints" {
    # Array of GET endpoints to test
    declare -a get_endpoints=(
        "getCounters:Get counters"
        "getAuthMethod:Get auth method"
        "getCache:Get cache"
        "getHashlookup:Get hash lookup"
        "getSendMailOnVirusUpload:Get send mail on virus upload"
        "getAutoScan:Get auto scan"
        "getPrefixMalicious:Get prefix malicious"
        "getDisableUnscannedTag:Get disable unscanned tag"
    )
    
    for endpoint_info in "${get_endpoints[@]}"; do
        IFS=':' read -r endpoint description <<< "$endpoint_info"
        echo "Testing $endpoint..."
        test_get_endpoint "$endpoint" "$description" "200"
    done
}

# Parameterized tests for POST endpoints with settings
@test "test POST settings endpoints" {
    # Array of POST endpoints with their test data
    declare -A post_endpoints=(
        ["setAutoScan"]='{"autoScan":"true"}'
        ["setPrefixMalicious"]='{"prefixMalicious":"[VIRUS] "}'
        ["setSendMailOnVirusUpload"]='{"sendMailOnVirusUpload":"false"}'
        ["setDisableUnscannedTag"]='{"disableUnscannedTag":"false"}'
        ["setadvancedconfig"]='{"maxScanFileSize":"104857600","maxUploadFileSize":"104857600"}'
    )
    
    for endpoint in "${!post_endpoints[@]}"; do
        echo "Testing $endpoint..."
        test_post_endpoint "$endpoint" "${post_endpoints[$endpoint]}" "Set ${endpoint#set}" "200"
    done
}

@test "test operator settings endpoint" {
    test_post_endpoint "operatorSettings" \
        '{"autoScan":"true","prefixMalicious":"[VIRUS]","sendMailOnVirusUpload":"false","disableUnscannedTag":"false"}' \
        "Set operator settings" "200"
}

@test "test admin settings endpoint" {
    test_post_endpoint "adminSettings" \
        '{"authMethod":"client-credentials","clientId":"test","clientSecret":"test","username":"","password":"","url":"https://gateway.production.vaas.gdatasecurity.de"}' \
        "Set admin settings" "200"
}

@test "test reset all tags endpoint" {
    test_post_endpoint "resetalltags" '{}' "Reset all tags" "200"
}

@test "test settings validation endpoint" {
    # Skip test if CLIENT_ID or CLIENT_SECRET are not set
    if [[ -z "$CLIENT_ID" || -z "$CLIENT_SECRET" ]]; then
        skip "CLIENT_ID and CLIENT_SECRET environment variables are required for this test"
    fi
    
    test_post_endpoint "testsettings" \
        '{"authMethod":"client-credentials","clientId":"'$CLIENT_ID'","clientSecret":"'$CLIENT_SECRET'","username":"","password":"","url":"https://gateway.production.vaas.gdatasecurity.de"}' \
        "Test settings" "200"
}

teardown_file() {
    rm -rf $FOLDER_PREFIX/
}
