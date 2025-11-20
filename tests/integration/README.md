# Integration Tests

This directory contains PHPUnit integration tests ported from the original BATS (Bash Automated Testing System) tests. These tests verify the functionality of the G DATA Antivirus app by testing real HTTP requests and Docker interactions.

## Overview

The integration tests are organized into the following test classes:

-   **BaseIntegrationTest.php**: Base class providing common functionality for HTTP requests, Docker interactions, and environment setup
-   **FileUploadTest.php**: Tests for file uploads via WebDAV (EICAR, clean files, PUP files)
-   **TagManagementTest.php**: Tests for file tagging functionality (won't scan, unscanned tags)
-   **ApiEndpointTest.php**: Tests for REST API endpoints (GET/POST operations, settings validation)

## Features Tested

### File Upload Tests

-   EICAR virus file uploads (should be blocked)
-   Clean file uploads (should succeed)
-   PUP (Potentially Unwanted Program) file uploads (should succeed)
-   Tests performed for both admin and test user accounts

### Tag Management Tests

-   "Won't scan" tags for files exceeding size limits
-   "Unscanned" tags for files that haven't been processed
-   Tag verification and counting

### API Endpoint Tests

-   GET endpoints for retrieving configuration and status
-   POST endpoints for updating settings
-   Authentication and authorization testing
-   Error handling (invalid endpoints, malformed requests, missing parameters)

## Prerequisites

1. **Docker Environment**: Tests require a running Nextcloud container named `nextcloud-container`
2. **Environment Variables**: Configure the following environment variables (see `.env-test` file):

    - `HOSTNAME`: Nextcloud server hostname (default: `127.0.0.1:8080`)
    - `TESTUSER`: Test user name (default: `testuser`)
    - `TESTUSER_PASSWORD`: Test user password
    - `CLIENT_SECRET`: G DATA VaaS client secret (optional, required for some tests)
    - `CLIENT_ID`: G DATA VaaS client ID (optional, required for validation tests)

3. **Dependencies**: Ensure all Composer dependencies are installed:
    ```bash
    composer install
    ```

## Running the Tests

### Run All Integration Tests

```bash
cd tests/integration
../vendor/bin/phpunit
```

### Run Specific Test Class

```bash
cd tests/integration
../vendor/bin/phpunit FileUploadTest.php
../vendor/bin/phpunit TagManagementTest.php
../vendor/bin/phpunit ApiEndpointTest.php
```

### Run Individual Test Method

```bash
cd tests/integration
../vendor/bin/phpunit --filter testAdminEicarUpload FileUploadTest.php
```

### Verbose Output

```bash
cd tests/integration
../vendor/bin/phpunit --verbose
```

## Test Environment Setup

The tests automatically perform the following setup operations:

1. Create temporary directory for test files
2. Download PUP test file from AMTSO
3. Create test user in Nextcloud
4. Configure G DATA VaaS client secret (if available)
5. Enable the G DATA VaaS app
6. Perform initial file scan

## Environment Configuration

The tests load configuration from multiple sources in this order:

1. `tests/bats/.env-test` (main test configuration)
2. `.env-local` (local overrides)
3. `.env` (fallback)
4. Built-in defaults

### Key Environment Variables

```bash
# Nextcloud server configuration
HOSTNAME=127.0.0.1:8080
MAIL_HOSTNAME=127.0.0.1:8081

# Test data configuration
FOLDER_PREFIX=./tmp/functionality-parallel
TESTUSER=testuser
TESTUSER_PASSWORD=myfancysecurepassword234

# Test files
EICAR_STRING='X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*'
CLEAN_STRING='nothingwronghere'

# Docker configuration
DOCKER_EXEC_WITH_USER='docker exec --env XDEBUG_MODE=off --user www-data'

# G DATA VaaS configuration (optional)
CLIENT_SECRET='your-client-secret'
CLIENT_ID='your-client-id'
```

## Test Data and Cleanup

-   **Temporary Files**: Tests create temporary files in `./tmp/functionality-parallel/`
-   **Test Files**: Downloaded PUP file and generated large files are created as needed
-   **Automatic Cleanup**: Tests automatically clean up created files after execution
-   **Container Files**: Files uploaded to the Docker container are removed after each test

## Troubleshooting

### Common Issues

1. **Docker Container Not Found**:

    - Ensure the Nextcloud container is running: `docker ps | grep nextcloud`
    - Check container name matches `nextcloud-container`

2. **Permission Errors**:

    - Verify Docker daemon is accessible
    - Check file permissions in temporary directory

3. **Network Connection Issues**:

    - Verify Nextcloud is accessible at configured hostname
    - Check firewall and port configuration

4. **Test File Download Failures**:
    - Check internet connection for PUP file download
    - Verify AMTSO test file availability

### Debug Output

Tests provide verbose output including:

-   HTTP response codes and bodies
-   Docker command execution results
-   File creation and cleanup status
-   Tag verification results

## Comparison with BATS Tests

These PHPUnit tests provide equivalent functionality to the original BATS tests with additional benefits:

-   **Better Error Handling**: More detailed error messages and debugging information
-   **Object-Oriented Structure**: Reusable components and cleaner test organization
-   **IDE Integration**: Better support for debugging and code navigation
-   **Type Safety**: PHP type hints and better parameter validation
-   **Extensibility**: Easier to add new tests and modify existing ones

## Contributing

When adding new integration tests:

1. Extend the appropriate test class or create a new one inheriting from `BaseIntegrationTest`
2. Follow the existing naming conventions
3. Include proper cleanup in `finally` blocks
4. Add appropriate assertions and error messages
5. Document any new environment variables or prerequisites
