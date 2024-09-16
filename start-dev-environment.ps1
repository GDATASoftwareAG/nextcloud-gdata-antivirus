if (-not $args[0]) {
    Write-Host "No server branch supplied. Using 30.0.0"
    $version = "30.0.0"
} else {
    $version = $args[0]
}

docker run -p 8080:80 -e SERVER_BRANCH=v$version -v "${PWD}:/var/www/html/apps-extra/gdatavaas" ghcr.io/juliushaertl/nextcloud-dev-php82:latest
