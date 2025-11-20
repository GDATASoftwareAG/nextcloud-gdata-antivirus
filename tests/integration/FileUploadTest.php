<?php

// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Integration;

class FileUploadTest extends BaseIntegrationTest
{
    /**
     * Test admin EICAR upload - should be blocked with virus found message
     */
    public function testAdminEicarUpload(): void
    {
        $filename = 'functionality-parallel.eicar.com.txt';
        
        try {
            $result = $this->uploadFileViaWebDAV('admin', 'admin', $filename, $this->eicarString);
            
            echo "EICAR upload result: {$result['http_code']}\n";
            echo "Response body: {$result['body']}\n";
            
            $this->assertContainsVirusFound($result);
        } finally {
            // Clean up - attempt to delete file if it exists
            $this->deleteFileViaWebDAV('admin', 'admin', $filename);
        }
    }

    /**
     * Test admin clean file upload - should succeed
     */
    public function testAdminCleanUpload(): void
    {
        $filename = 'functionality-parallel.clean.txt';
        
        try {
            $result = $this->uploadFileViaWebDAV('admin', 'admin', $filename, $this->cleanString);
            
            echo "Clean file upload result: {$result['http_code']}\n";
            
            $this->assertHttpCodeInRange($result['http_code'], 200, 299);
        } finally {
            // Clean up
            $this->deleteFileViaWebDAV('admin', 'admin', $filename);
        }
    }

    /**
     * Test admin PUP (Potentially Unwanted Program) upload - should succeed
     */
    public function testAdminPupUpload(): void
    {
        $filename = 'functionality-parallel.pup.exe';
        $pupFilePath = $this->folderPrefix . '/pup.exe';
        
        if (!file_exists($pupFilePath)) {
            $this->markTestSkipped('PUP file not available');
        }

        try {
            $result = $this->uploadFileFromDisk('admin', 'admin', $filename, $pupFilePath);
            
            echo "PUP upload result: {$result['http_code']}\n";
            
            $this->assertHttpCodeInRange($result['http_code'], 200, 299);
        } finally {
            // Clean up
            $this->deleteFileViaWebDAV('admin', 'admin', $filename);
        }
    }

    /**
     * Test testuser EICAR upload - should be blocked with virus found message
     */
    public function testTestUserEicarUpload(): void
    {
        $filename = 'functionality-parallel.eicar.com.txt';
        
        try {
            $result = $this->uploadFileViaWebDAV($this->testUser, $this->testUserPassword, $filename, $this->eicarString);
            
            echo "Testuser EICAR upload result: {$result['http_code']}\n";
            echo "Response body: {$result['body']}\n";
            
            // Log client secret for debugging
            $configResult = $this->executeDockerCommand("php occ config:app:get gdatavaas clientSecret");
            echo "Client secret configured: " . (empty($configResult['output']) ? 'No' : 'Yes') . "\n";
            
            $this->assertContainsVirusFound($result);
        } finally {
            // Clean up
            $this->deleteFileViaWebDAV($this->testUser, $this->testUserPassword, $filename);
        }
    }

    /**
     * Test testuser clean file upload - should succeed
     */
    public function testTestUserCleanUpload(): void
    {
        $filename = 'functionality-parallel.clean.txt';
        
        try {
            $result = $this->uploadFileViaWebDAV($this->testUser, $this->testUserPassword, $filename, $this->cleanString);
            
            echo "Testuser clean file upload result: {$result['http_code']}\n";
            
            $this->assertHttpCodeInRange($result['http_code'], 200, 299);
        } finally {
            // Clean up
            $this->deleteFileViaWebDAV($this->testUser, $this->testUserPassword, $filename);
        }
    }

    /**
     * Test testuser PUP upload - should succeed
     */
    public function testTestUserPupUpload(): void
    {
        $filename = 'functionality-parallel.pup.exe';
        $pupFilePath = $this->folderPrefix . '/pup.exe';
        
        if (!file_exists($pupFilePath)) {
            $this->markTestSkipped('PUP file not available');
        }

        try {
            $result = $this->uploadFileFromDisk($this->testUser, $this->testUserPassword, $filename, $pupFilePath);
            
            echo "Testuser PUP upload result: {$result['http_code']}\n";
            
            $this->assertHttpCodeInRange($result['http_code'], 200, 299);
        } finally {
            // Clean up
            $this->deleteFileViaWebDAV($this->testUser, $this->testUserPassword, $filename);
        }
    }
}