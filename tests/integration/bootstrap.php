<?php

// SPDX-FileCopyrightText: 2025 G DATA CyberDefense AG <vaas@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

require_once __DIR__ . '/../../vendor/autoload.php';

// Define constants for integration testing
define('INTEGRATION_TEST_ROOT', __DIR__);
define('PROJECT_ROOT', __DIR__ . '/../..');

// Load environment variables for integration testing
$envFile = __DIR__ . '/../bats/.env-test';
if (file_exists($envFile)) {
	$envContent = file_get_contents($envFile);
	$envLines = explode("\n", $envContent);

	foreach ($envLines as $line) {
		$line = trim($line);
		if (empty($line) || strpos($line, '#') === 0 || strpos($line, 'export') !== 0) {
			continue;
		}

		// Remove 'export ' prefix and parse key=value
		$line = substr($line, 7); // Remove 'export '
		if (strpos($line, '=') !== false) {
			[$key, $value] = explode('=', $line, 2);
			// Remove quotes if present
			$value = trim($value, '"\'');
			$_ENV[$key] = $value;
			putenv("$key=$value");
		}
	}
}

// Load local environment files
$localEnvFiles = [PROJECT_ROOT . '/.env-local', PROJECT_ROOT . '/.env'];
foreach ($localEnvFiles as $envFile) {
	if (file_exists($envFile)) {
		$envContent = file_get_contents($envFile);
		$envLines = explode("\n", $envContent);

		foreach ($envLines as $line) {
			$line = trim($line);
			if (empty($line) || strpos($line, '#') === 0) {
				continue;
			}

			if (strpos($line, '=') !== false) {
				[$key, $value] = explode('=', $line, 2);
				// Remove quotes if present
				$value = trim($value, '"\'');
				$_ENV[$key] = $value;
				putenv("$key=$value");
			}
		}
		break; // Use first found env file
	}
}

// Set default values if not defined
if (!isset($_ENV['HOSTNAME'])) {
	$_ENV['HOSTNAME'] = '127.0.0.1:8080';
	putenv('HOSTNAME=127.0.0.1:8080');
}

if (!isset($_ENV['FOLDER_PREFIX'])) {
	$_ENV['FOLDER_PREFIX'] = './tmp/functionality-parallel';
	putenv('FOLDER_PREFIX=./tmp/functionality-parallel');
}

if (!isset($_ENV['TESTUSER'])) {
	$_ENV['TESTUSER'] = 'testuser';
	putenv('TESTUSER=testuser');
}

if (!isset($_ENV['TESTUSER_PASSWORD'])) {
	$_ENV['TESTUSER_PASSWORD'] = 'myfancysecurepassword234';
	putenv('TESTUSER_PASSWORD=myfancysecurepassword234');
}

if (!isset($_ENV['EICAR_STRING'])) {
	$_ENV['EICAR_STRING'] = 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';
	putenv('EICAR_STRING=X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*');
}

if (!isset($_ENV['CLEAN_STRING'])) {
	$_ENV['CLEAN_STRING'] = 'nothingwronghere';
	putenv('CLEAN_STRING=nothingwronghere');
}

if (!isset($_ENV['DOCKER_EXEC_WITH_USER'])) {
	$_ENV['DOCKER_EXEC_WITH_USER'] = 'docker exec --env XDEBUG_MODE=off --user www-data';
	putenv('DOCKER_EXEC_WITH_USER=docker exec --env XDEBUG_MODE=off --user www-data');
}
