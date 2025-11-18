<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Lennart Dohmann <lennart.dohmann@gdata.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

return [
	'resources' => [],
	'routes' => [
		// user
		['name' => 'scan#scan', 'url' => '/scan', 'verb' => 'POST'],
		['name' => 'settings#getCounters', 'url' => '/getCounters', 'verb' => 'GET'],
		// operator
		['name' => 'settings#setOperatorSettings', 'url' => '/operatorSettings', 'verb' => 'POST'],
		['name' => 'settings#getSendMailOnVirusUpload', 'url' => '/getSendMailOnVirusUpload', 'verb' => 'GET'],
		['name' => 'settings#setSendMailOnVirusUpload', 'url' => '/setSendMailOnVirusUpload', 'verb' => 'POST'],
		['name' => 'settings#setAutoScan', 'url' => '/setAutoScan', 'verb' => 'POST'],
		['name' => 'settings#getAutoScan', 'url' => '/getAutoScan', 'verb' => 'GET'],
		['name' => 'settings#setPrefixMalicious', 'url' => '/setPrefixMalicious', 'verb' => 'POST'],
		['name' => 'settings#getPrefixMalicious', 'url' => '/getPrefixMalicious', 'verb' => 'GET'],
		['name' => 'settings#setDisableUnscannedTag', 'url' => '/setDisableUnscannedTag', 'verb' => 'POST'],
		['name' => 'settings#getDisableUnscannedTag', 'url' => '/getDisableUnscannedTag', 'verb' => 'GET'],
		// admin
		['name' => 'settings#setAdminSettings', 'url' => '/adminSettings', 'verb' => 'POST'],
		['name' => 'settings#setadvancedconfig', 'url' => '/setadvancedconfig', 'verb' => 'POST'],
		['name' => 'settings#getAuthMethod', 'url' => '/getAuthMethod', 'verb' => 'GET'],
		['name' => 'settings#resetAllTags', 'url' => '/resetalltags', 'verb' => 'POST'],
		['name' => 'settings#testsettings', 'url' => '/testsettings', 'verb' => 'POST'],
		['name' => 'settings#getCache', 'url' => '/getCache', 'verb' => 'GET'],
		['name' => 'settings#getHashlookup', 'url' => '/getHashlookup', 'verb' => 'GET'],
	]
];
