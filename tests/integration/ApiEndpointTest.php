<?php

// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Integration;

class ApiEndpointTest extends BaseIntegrationTest
{
    /**
     * Test scan endpoint (POST /scan)
     */
    public function testScanEndpoint(): void
    {
        // Create a test file first
        $filename = 'test-scan.txt';
        $containerPath = "/var/www/html/data/admin/files/{$filename}";
        $testFile = $this->folderPrefix . '/test-scan.txt';
        
        // Create local test file
        file_put_contents($testFile, $this->cleanString);
        
        try {
            // Copy to container
            exec("docker cp {$testFile} nextcloud-container:{$containerPath}", $output, $returnCode);
            if ($returnCode !== 0) {
                $this->markTestSkipped('Could not copy test file to container');
            }
            
            // Set proper ownership
            $this->executeDockerCommand("chown www-data:www-data {$containerPath}");
            
            // Scan admin files
            $this->executeDockerCommand("php occ files:scan admin");
            
            // Test scan endpoint
            $this->testPostEndpoint('scan', ['path' => $filename], 'Scan endpoint', 200);
            
        } finally {
            // Clean up
            $this->executeDockerCommand("rm -f {$containerPath}");
            if (file_exists($testFile)) {
                unlink($testFile);
            }
        }
    }

    /**
     * Test all GET endpoints
     * @dataProvider getEndpointsProvider
     */
    public function testGetEndpoints(string $endpoint, string $description): void
    {
        echo "Testing {$endpoint}...\n";
        $this->testGetEndpoint($endpoint, $description, 200);
    }

    /**
     * Data provider for GET endpoints
     */
    public static function getEndpointsProvider(): array
    {
        return [
            ['getCounters', 'Get counters'],
            ['getAuthMethod', 'Get auth method'],
            ['getCache', 'Get cache'],
            ['getHashlookup', 'Get hash lookup'],
            ['getSendMailOnVirusUpload', 'Get send mail on virus upload'],
            ['getAutoScan', 'Get auto scan'],
            ['getPrefixMalicious', 'Get prefix malicious'],
            ['getDisableUnscannedTag', 'Get disable unscanned tag'],
        ];
    }

    /**
     * Test all POST settings endpoints
     * @dataProvider postEndpointsProvider
     */
    public function testPostSettingsEndpoints(string $endpoint, array $data, string $description): void
    {
        echo "Testing {$endpoint}...\n";
        $this->testPostEndpoint($endpoint, $data, $description, 200);
    }

    /**
     * Data provider for POST settings endpoints
     */
    public static function postEndpointsProvider(): array
    {
        return [
            ['setAutoScan', ['autoScan' => 'true'], 'Set auto scan'],
            ['setPrefixMalicious', ['prefixMalicious' => '[VIRUS] '], 'Set prefix malicious'],
            ['setSendMailOnVirusUpload', ['sendMailOnVirusUpload' => 'false'], 'Set send mail on virus upload'],
            ['setDisableUnscannedTag', ['disableUnscannedTag' => 'false'], 'Set disable unscanned tag'],
            ['setadvancedconfig', ['maxScanFileSize' => '104857600', 'maxUploadFileSize' => '104857600'], 'Set advanced config'],
        ];
    }

    /**
     * Test operator settings endpoint
     */
    public function testOperatorSettingsEndpoint(): void
    {
        $data = [
            'autoScan' => 'true',
            'prefixMalicious' => '[VIRUS]',
            'sendMailOnVirusUpload' => 'false',
            'disableUnscannedTag' => 'false'
        ];
        
        $this->testPostEndpoint('operatorSettings', $data, 'Set operator settings', 200);
    }

    /**
     * Test admin settings endpoint
     */
    public function testAdminSettingsEndpoint(): void
    {
        $data = [
            'authMethod' => 'client-credentials',
            'clientId' => 'test',
            'clientSecret' => 'test',
            'username' => '',
            'password' => '',
            'url' => 'https://gateway.production.vaas.gdatasecurity.de'
        ];
        
        $this->testPostEndpoint('adminSettings', $data, 'Set admin settings', 200);
    }

    /**
     * Test reset all tags endpoint
     */
    public function testResetAllTagsEndpoint(): void
    {
        $this->testPostEndpoint('resetalltags', [], 'Reset all tags', 200);
    }

    /**
     * Test settings validation endpoint
     * This test requires valid CLIENT_ID and CLIENT_SECRET environment variables
     */
    public function testSettingsValidationEndpoint(): void
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            $this->markTestSkipped('CLIENT_ID and CLIENT_SECRET environment variables are required for this test');
        }
        
        $data = [
            'authMethod' => 'client-credentials',
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'username' => '',
            'password' => '',
            'url' => 'https://gateway.production.vaas.gdatasecurity.de'
        ];
        
        $this->testPostEndpoint('testsettings', $data, 'Test settings', 200);
    }

    /**
     * Test unauthorized access to admin endpoints
     * Tests that non-admin users get appropriate error responses
     */
    public function testUnauthorizedAccess(): void
    {
        // Test with regular user credentials (should fail for admin endpoints)
        $this->testGetEndpoint('getCounters', 'Unauthorized get counters', 401, $this->testUser, $this->testUserPassword);
    }

    /**
     * Test invalid endpoint
     * Tests that non-existent endpoints return 404
     */
    public function testInvalidEndpoint(): void
    {
        $url = "http://{$this->hostname}/apps/gdatavaas/nonexistent";
        
        $result = $this->makeHttpRequest('GET', $url, [
            'auth' => ['username' => 'admin', 'password' => 'admin']
        ]);
        
        echo "Invalid endpoint result: {$result['http_code']}\n";
        $this->assertEquals(404, $result['http_code'], 'Expected 404 for non-existent endpoint');
    }

    /**
     * Test malformed JSON in POST requests
     */
    public function testMalformedJsonPost(): void
    {
        $url = "http://{$this->hostname}/apps/gdatavaas/setAutoScan";
        
        $result = $this->makeHttpRequest('POST', $url, [
            'auth' => ['username' => 'admin', 'password' => 'admin'],
            'body' => '{"invalid": json}', // Malformed JSON
            'headers' => ['Content-Type: application/json']
        ]);
        
        echo "Malformed JSON result: {$result['http_code']}\n";
        // Depending on implementation, this might return 400 or 500
        $this->assertContains($result['http_code'], [400, 500], 'Expected error status for malformed JSON');
    }

    /**
     * Test endpoint with missing required parameters
     */
    public function testMissingParameters(): void
    {
        // Test scan endpoint without required 'path' parameter
        $result = $this->makeHttpRequest('POST', "http://{$this->hostname}/apps/gdatavaas/scan", [
            'auth' => ['username' => 'admin', 'password' => 'admin'],
            'body' => json_encode([]), // Empty data
            'headers' => ['Content-Type: application/json']
        ]);
        
        echo "Missing parameters result: {$result['http_code']}\n";
        // Should return an error status
        $this->assertGreaterThanOrEqual(400, $result['http_code'], 'Expected error status for missing parameters');
    }
}