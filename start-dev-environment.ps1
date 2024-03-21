if (-not $args[0]) {
    Write-Host "No server branch supplied. Using 28.0.3"
    $version = "28.0.3"
} else {
    $version = $args[0]
}

docker run -p 8080:80 -e SERVER_BRANCH=v$version -v "${PWD}:/var/www/html/apps-extra/gdatavaas" ghcr.io/juliushaertl/nextcloud-dev-php82:latest
