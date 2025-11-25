<?php

// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Integration;

use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/BaseIntegrationTest.php';

class SettingsControllerTest extends BaseIntegrationTest {
	public static function adminGetRouteProvider(): array {
		return [
			['getAuthMethod'],
			['getCache'],
			['getHashlookup'],
		];
	}

	public static function adminPostRouteProvider(): array {
		return [
			// TODO: use default settings
			['adminSettings', [
				'username' => 'username',
				'password' => 'password',
				'clientId' => 'clientId',
				'clientSecret' => 'clientSecret',
				'authMethod' => 'authMethod',
				'maxScanSize' => 209715200,
				'timeout' => 900,
				'cache' => true,
				'hashlookup' => true
			]],
			// ['setadvancedconfig'],
		];
	}

	public static function operatorGetRouteProvider(): array {
		return [
			['getSendMailOnVirusUpload'],
			['getAutoScan'],
			['getPrefixMalicious'],
			['getDisableUnscannedTag'],
			['getCounters'],
		];
	}

	public static function operatorPostRouteProvider(): array {
		return [
			['operatorSettings', [
				'quarantineFolder' => '',
				'scanOnlyThis' => '',
				'doNotScanThis' => '',
				'notifyMails' => '',
			]],
			['setAutoScan', ['autoScanFiles' => true]],
			['setPrefixMalicious', ['prefixMalicious' => '[VIRUS] ']],
			['setSendMailOnVirusUpload', ['sendMailOnVirusUpload' => 'false']],
			['setDisableUnscannedTag', ['disableUnscannedTag' => 'false']],
		];
	}

	#[DataProvider('adminGetRouteProvider')]
	public function testAdminCanAccessAdminGetRoutes(string $route): void {
		$this->testGetEndpoint($route, "Admin access to {$route}", 200);
	}


	#[DataProvider('adminPostRouteProvider')]
	public function testAdminCanAccessAdminPostRoutes(string $route, array $data): void {
		$this->testPostEndpoint($route, $data, "Admin access to {$route}", 200);
	}

	#[DataProvider('operatorGetRouteProvider')]
	public function testAdminCanAccessOperatorGetRoutes(string $route): void {
		$this->testGetEndpoint($route, "Admin access to {$route}", 200);
	}

	#[DataProvider('operatorPostRouteProvider')]
	public function testAdminCanAccessOperatorPostRoutes(string $route, array $data): void {
		$this->testPostEndpoint($route, $data, "Admin access to {$route}", 200);
	}


	#[DataProvider('adminGetRouteProvider')]
	public function testOperatorCannotAccessAdminGetRoutes(string $route): void {
		$this->testGetEndpoint($route, "Operator access to {$route}", 403, username: 'vaas-operator', password: 'gdatavaas-operator');
	}


	#[DataProvider('adminPostRouteProvider')]
	public function testOperatorCannotAccessAdminPostRoutes(string $route, array $data): void {
		$this->testPostEndpoint($route, $data, "Operator access to {$route}", 403, username: 'vaas-operator', password: 'gdatavaas-operator');
	}

	#[DataProvider('operatorGetRouteProvider')]
	public function testOperatorCanAccessOperatorGetRoutes(string $route): void {
		$this->testGetEndpoint($route, "Operator access to {$route}", 200, username: 'vaas-operator', password: 'gdatavaas-operator');
	}

	#[DataProvider('operatorPostRouteProvider')]
	public function testOperatorCanAccessOperatorPostRoutes(string $route, array $data): void {
		$this->testPostEndpoint($route, $data, "Operator access to {$route}", 200, username: 'vaas-operator', password: 'gdatavaas-operator');
	}


	#[DataProvider('adminGetRouteProvider')]
	public function testUserCannotAccessAdminGetRoutes(string $route): void {
		$this->testGetEndpoint($route, "User access to {$route}", 403, username: 'user', password: 'gdatavaas-user');
	}


	#[DataProvider('adminPostRouteProvider')]
	public function testUserCannotAccessAdminPostRoutes(string $route, array $data): void {
		$this->testPostEndpoint($route, $data, "Operator access to {$route}", 403, username: 'user', password: 'gdatavaas-user');
	}

	#[DataProvider('operatorGetRouteProvider')]
	public function testUserCannotAccessOperatorGetRoutes(string $route): void {
		$this->testGetEndpoint($route, "Operator access to {$route}", 403, username: 'user', password: 'gdatavaas-user');
	}

	#[DataProvider('operatorPostRouteProvider')]
	public function testUserCannotAccessOperatorPostRoutes(string $route, array $data): void {
		$this->testPostEndpoint($route, $data, "Operator access to {$route}", 403, username: 'user', password: 'gdatavaas-user');
	}


	#[DataProvider('adminPostRouteProvider')]
	#[DataProvider('operatorPostRouteProvider')]
	public function testPostRoutesReturn400ForEmptyBody(string $route, array $data): void {
		$this->testPostEndpoint($route, [], "Admin access to {$route}", 400);
	}
}
