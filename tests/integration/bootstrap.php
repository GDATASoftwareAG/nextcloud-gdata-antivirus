<?php

// SPDX-FileCopyrightText: 2025 G DATA CyberDefense AG <vaas@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

use Dotenv\Dotenv;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Search upward from the given directory for a .env file.
 */
function findEnvDirectory(string $startDir): ?string
{
	$dir = $startDir;

	while (true) {
		if (file_exists($dir . '/.env')) {
			return $dir;
		}

		$parent = dirname($dir);

		// Stop if we've reached the root directory
		if ($parent === $dir) {
			return null;
		}

		$dir = $parent;
	}
}

$envDir = findEnvDirectory(__DIR__);

if ($envDir !== null) {
	$dotenv = Dotenv::createImmutable($envDir);
	$dotenv->safeLoad();
}
