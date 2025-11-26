<?php

// SPDX-FileCopyrightText: 2025 G DATA CyberDefense AG <vaas@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

require_once __DIR__ . '/../../vendor/autoload.php';

define('PROJECT_ROOT', __DIR__ . '/../..');

$dotenv = Dotenv\Dotenv::createImmutable(PROJECT_ROOT);
$dotenv->load();
