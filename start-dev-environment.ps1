if (-not $args[0]) {
    Write-Host "No server branch supplied. Using 27.1.6"
    $version = "27.1.6"
} else {
    $version = $args[0]
}

docker run -p 8080:80 -e SERVER_BRANCH=v$version -v "${PWD}:/var/www/html/apps-extra/gdatavaas" -v ./dev-environment:/var/www/html ghcr.io/juliushaertl/nextcloud-dev-php82:latest
