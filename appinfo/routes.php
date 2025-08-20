<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Lennart Dohmann <lennart.dohmann@gdata.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

return [
	'resources' => [],
	'routes' => [
		['name' => 'scan#scan', 'url' => '/scan', 'verb' => 'POST'],
		['name' => 'settings#setconfig', 'url' => '/setconfig', 'verb' => 'POST'],
		['name' => 'settings#setadvancedconfig', 'url' => '/setadvancedconfig', 'verb' => 'POST'],
		['name' => 'settings#setAutoScan', 'url' => '/setAutoScan', 'verb' => 'POST'],
		['name' => 'settings#getAutoScan', 'url' => '/getAutoScan', 'verb' => 'GET'],
		['name' => 'settings#setScanOnlyNewFiles', 'url' => '/setScanOnlyNewFiles', 'verb' => 'POST'],
		['name' => 'settings#getScanOnlyNewFiles', 'url' => '/getScanOnlyNewFiles', 'verb' => 'GET'],
		['name' => 'settings#setPrefixMalicious', 'url' => '/setPrefixMalicious', 'verb' => 'POST'],
		['name' => 'settings#getPrefixMalicious', 'url' => '/getPrefixMalicious', 'verb' => 'GET'],
		['name' => 'settings#getAuthMethod', 'url' => '/getAuthMethod', 'verb' => 'GET'],
		['name' => 'settings#setDisableUnscannedTag', 'url' => '/setDisableUnscannedTag', 'verb' => 'POST'],
		['name' => 'settings#getDisableUnscannedTag', 'url' => '/getDisableUnscannedTag', 'verb' => 'GET'],
		['name' => 'settings#resetAllTags', 'url' => '/resetalltags', 'verb' => 'POST'],
		['name' => 'settings#getCounters', 'url' => '/getCounters', 'verb' => 'GET'],
		['name' => 'settings#getSendMailOnVirusUpload', 'url' => '/getSendMailOnVirusUpload', 'verb' => 'GET'],
		['name' => 'settings#setSendMailOnVirusUpload', 'url' => '/setSendMailOnVirusUpload', 'verb' => 'POST'],
		['name' => 'settings#getSendMailSummaryOfMaliciousFiles', 'url'
			=> '/getSendMailSummaryOfMaliciousFiles', 'verb' => 'GET'],
		['name' => 'settings#setSendMailSummaryOfMaliciousFiles', 'url'
			=> '/setSendMailSummaryOfMaliciousFiles', 'verb' => 'POST']
	]
];
