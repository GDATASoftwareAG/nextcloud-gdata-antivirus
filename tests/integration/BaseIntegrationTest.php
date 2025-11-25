<?php

// SPDX-FileCopyrightText: 2025 G DATA CyberDefense AG <vaas@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Integration;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use PHPUnit\Framework\TestCase;
use RuntimeException;

abstract class BaseIntegrationTest extends TestCase {
	protected string $hostname;
	protected string $folderPrefix;
	protected string $testUser;
	protected string $testUserPassword;
	protected string $eicarString;
	protected string $cleanString;
	protected string $dockerExecWithUser;
	protected string $clientSecret;
	protected string $clientId;

	protected static bool $setupCompleted = false;

	protected function setUp(): void {
		parent::setUp();

		// Load environment variables
		$this->hostname = $_ENV['HOSTNAME'] ?? '127.0.0.1:8080';
		$this->folderPrefix = $_ENV['FOLDER_PREFIX'] ?? './tmp/functionality-parallel';
		$this->testUser = $_ENV['TESTUSER'] ?? 'testuser';
		$this->testUserPassword = $_ENV['TESTUSER_PASSWORD'] ?? 'myfancysecurepassword234';
		$this->eicarString = $_ENV['EICAR_STRING'] ?? 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';
		$this->cleanString = $_ENV['CLEAN_STRING'] ?? 'nothingwronghere';
		$this->dockerExecWithUser = $_ENV['DOCKER_EXEC_WITH_USER'] ?? 'docker exec --env XDEBUG_MODE=off --user www-data';
		$this->clientSecret = $_ENV['CLIENT_SECRET'] ?? '';
		$this->clientId = $_ENV['CLIENT_ID'] ?? '';

		if (!self::$setupCompleted) {
			$this->setupEnvironment();
			self::$setupCompleted = true;
		}
	}

	protected function setupEnvironment(): void {
		// Create temporary folder
		if (!is_dir($this->folderPrefix)) {
			mkdir($this->folderPrefix, 0755, true);
		}

		// Download PUP file
		$pupFile = $this->folderPrefix . '/pup.exe';
		if (!file_exists($pupFile)) {
			$pupContent = file_get_contents('http://amtso.eicar.org/PotentiallyUnwanted.exe');
			if ($pupContent === false) {
				$this->markTestSkipped('Could not download PUP file from AMTSO');
			}
			file_put_contents($pupFile, $pupContent);
		}

		// Create test user
		$this->executeDockerCommand("php occ user:add {$this->testUser} --password-from-env", [
			'OC_PASS' => $this->testUserPassword
		]);

		// Create user directory
		$this->executeDockerCommand("mkdir -p /var/www/html/data/{$this->testUser}/files");

		// Set client secret if available
		if (!empty($this->clientSecret)) {
			$this->executeDockerCommand("php occ config:app:set gdatavaas clientSecret --value=\"{$this->clientSecret}\"");
		}

		// Cache busting and enable app
		$this->executeDockerCommand('php occ files:scan --all');
		sleep(2);
		$this->executeDockerCommand('php occ app:enable gdatavaas');
	}

	protected function executeDockerCommand(string $command, array $env = []): array {
		$envString = '';
		foreach ($env as $key => $value) {
			$envString .= " --env {$key}=\"{$value}\"";
		}

		$fullCommand = "{$this->dockerExecWithUser}{$envString} nextcloud-container {$command}";

		exec($fullCommand . ' 2>&1', $output, $returnCode);

		return [
			'output' => $output,
			'return_code' => $returnCode,
			'command' => $fullCommand
		];
	}

	protected function makeHttpRequest(string $method, string $url, array $options = []): array {
		$client = HttpClientBuilder::buildDefault();
		$request = new Request($url, $method);

		$request->setHeader('OCS-APIRequest', 'true');

		// Add authentication if provided
		if (isset($options['auth'])) {
			$credentials = base64_encode($options['auth']['username'] . ':' . $options['auth']['password']);
			$request->removeHeader('Authorization');
			$request->setHeader('Authorization', 'Basic ' . $credentials);
		}

		// Add request body if provided
		if (isset($options['body'])) {
			$request->setBody($options['body']);
		}

		// Add headers if provided
		if (isset($options['headers'])) {
			foreach ($options['headers'] as $header) {
				if (is_string($header) && strpos($header, ':') !== false) {
					[$name, $value] = explode(':', $header, 2);
					$request->setHeader(trim($name), trim($value));
				}
			}
		}

		// Handle file upload for PUT requests
		if (isset($options['file_handle'])) {
			$request->setBody($options['file_handle']);
		}

		try {
			$response = $client->request($request);

			return [
				'body' => $response->getBody()->buffer(),
				'http_code' => $response->getStatus(),
				'error' => ''
			];
		} catch (\Throwable $e) {
			return [
				'body' => '',
				'http_code' => 0,
				'error' => $e->getMessage()
			];
		}
	}

	protected function uploadFileViaWebDAV(string $username, string $password, string $filename, string $content): array {
		$url = "http://{$this->hostname}/remote.php/dav/files/{$username}/{$filename}";

		return $this->makeHttpRequest('PUT', $url, [
			'auth' => ['username' => $username, 'password' => $password],
			'body' => $content,
			'headers' => ['Content-Type: text/plain']
		]);
	}

	protected function deleteFileViaWebDAV(string $username, string $password, string $filename): array {
		$url = "http://{$this->hostname}/remote.php/dav/files/{$username}/{$filename}";

		return $this->makeHttpRequest('DELETE', $url, [
			'auth' => ['username' => $username, 'password' => $password]
		]);
	}

	protected function uploadFileFromDisk(string $username, string $password, string $filename, string $localPath): array {
		if (!file_exists($localPath)) {
			throw new RuntimeException("File not found: {$localPath}");
		}

		$url = "http://{$this->hostname}/remote.php/dav/files/{$username}/{$filename}";
		$fileContent = file_get_contents($localPath);

		if ($fileContent === false) {
			throw new RuntimeException("Could not read file: {$localPath}");
		}

		return $this->makeHttpRequest('PUT', $url, [
			'auth' => ['username' => $username, 'password' => $password],
			'body' => $fileContent,
		]);
	}

	protected function testGetEndpoint(
		string $endpoint, 
		string $description, 
		int $expectedHttpStatus = 200, 
		string $username = 'admin', 
		string $password = 'admin'
	): void {
		$url = "http://{$this->hostname}/apps/gdatavaas/{$endpoint}";

		$result = $this->makeHttpRequest('GET', $url, [
			'auth' => ['username' => $username, 'password' => $password]
		]);

		$this->assertEquals($expectedHttpStatus, $result['http_code'], "Failed: {$description}");
	}

	protected function testPostEndpoint(
		string $endpoint, 
		array $data, 
		string $description, 
		int $expectedHttpStatus = 200, 
		string $username = 'admin', 
		string $password = 'admin'
	): void {
		$url = "http://{$this->hostname}/apps/gdatavaas/{$endpoint}";

		$result = $this->makeHttpRequest('POST', $url, [
			'auth' => ['username' => $username, 'password' => $password],
			'body' => json_encode($data),
			'headers' => ['Content-Type: application/json']
		]);

		$this->assertEquals($expectedHttpStatus, $result['http_code'], "Failed: {$description}");
	}

	protected function assertContainsVirusFound(array $response): void {
		$this->assertStringContainsString('Virus found', $response['body'], 'Expected "Virus found" in response body');
	}

	protected function assertHttpCodeInRange(int $httpCode, int $min = 200, int $max = 299): void {
		$this->assertGreaterThanOrEqual($min, $httpCode, "HTTP code {$httpCode} is below expected range {$min}-{$max}");
		$this->assertLessThan($max + 1, $httpCode, "HTTP code {$httpCode} is above expected range {$min}-{$max}");
	}

	protected function getTagsForFile(string $filePath): array {
		$result = $this->executeDockerCommand("php occ gdatavaas:get-tags-for-file {$filePath}");
		return $result['output'];
	}

	protected function assertHasTag(string $filePath, string $expectedTag): void {
		$tags = $this->getTagsForFile($filePath);
		$tagString = implode("\n", $tags);
		$this->assertStringContainsString($expectedTag, $tagString, "Expected tag '{$expectedTag}' not found in file tags");
	}

	protected function assertTagCount(string $filePath, int $expectedCount): void {
		$tags = $this->getTagsForFile($filePath);
		// Filter out empty lines
		$nonEmptyTags = array_filter($tags, function ($line) {
			return trim($line) !== '';
		});
		$this->assertCount($expectedCount, $nonEmptyTags, "Expected {$expectedCount} tags, got " . count($nonEmptyTags));
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		// Clean up temporary files
		$folderPrefix = $_ENV['FOLDER_PREFIX'] ?? './tmp/functionality-parallel';
		if (is_dir($folderPrefix)) {
			self::removeDirectory($folderPrefix);
		}
	}

	private static function removeDirectory(string $dir): void {
		if (!is_dir($dir)) {
			return;
		}

		$files = array_diff(scandir($dir), ['.', '..']);

		foreach ($files as $file) {
			$path = $dir . DIRECTORY_SEPARATOR . $file;
			if (is_dir($path)) {
				self::removeDirectory($path);
			} else {
				unlink($path);
			}
		}

		rmdir($dir);
	}
}
