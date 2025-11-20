<?php

// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Integration;

class TagManagementTest extends BaseIntegrationTest
{
    /**
     * Test "Won't scan" tag for testuser with large file
     * Files larger than the configured scan limit should get the "Won't scan" tag
     */
    public function testWontScanTagForTestUser(): void
    {
        $largeFileName = "{$this->testUser}.too-large.dat";
        $localLargeFile = $this->folderPrefix . '/too-large.dat';
        $containerPath = "/var/www/html/data/{$this->testUser}/files/{$largeFileName}";
        
        // Create large file (256MB + 1 byte = 268,435,457 bytes)
        // This uses dd command equivalent in PHP
        if (!file_exists($localLargeFile)) {
            $this->createLargeFile($localLargeFile, 268435457);
        }
        
        try {
            // Copy large file to container
            $copyResult = $this->executeDockerCommand("cp {$localLargeFile} nextcloud-container:{$containerPath}");
            if ($copyResult['return_code'] !== 0) {
                // Alternative approach: use docker cp command directly
                exec("docker cp {$localLargeFile} nextcloud-container:{$containerPath}", $output, $returnCode);
                if ($returnCode !== 0) {
                    $this->markTestSkipped('Could not copy large file to container');
                }
            }
            
            // Scan all files
            $this->executeDockerCommand("php occ files:scan --all");
            
            // Run GDATA VaaS scan
            $this->executeDockerCommand("php occ gdatavaas:scan");
            
            // Get tags for the file
            $filePath = "{$this->testUser}/files/{$largeFileName}";
            
            // Verify "Won't scan" tag is present
            $this->assertHasTag($filePath, "Won't scan");
            
            // Verify only one tag is present
            $this->assertTagCount($filePath, 1);
            
        } finally {
            // Clean up - remove file from container
            $this->executeDockerCommand("rm -f {$containerPath}");
            
            // Clean up local large file
            if (file_exists($localLargeFile)) {
                unlink($localLargeFile);
            }
        }
    }

    /**
     * Test unscanned job for admin user
     * Files that haven't been scanned should get the "Unscanned" tag when running the tag-unscanned command
     */
    public function testUnscannedJobForAdmin(): void
    {
        $filename = 'admin.unscanned.pup.exe';
        $pupFilePath = $this->folderPrefix . '/pup.exe';
        $containerPath = "/var/www/html/data/admin/files/{$filename}";
        
        if (!file_exists($pupFilePath)) {
            $this->markTestSkipped('PUP file not available');
        }
        
        try {
            // Copy PUP file directly to container (bypassing normal upload process)
            exec("docker cp {$pupFilePath} nextcloud-container:{$containerPath}", $output, $returnCode);
            if ($returnCode !== 0) {
                $this->markTestSkipped('Could not copy PUP file to container for admin');
            }
            
            // Set proper ownership
            $this->executeDockerCommand("chown www-data:www-data {$containerPath}");
            
            // Scan files for admin user only
            $this->executeDockerCommand("php occ files:scan admin");
            
            // Tag unscanned files
            $this->executeDockerCommand("php occ gdatavaas:tag-unscanned");
            
            // Verify "Unscanned" tag is present
            $filePath = "admin/files/{$filename}";
            $this->assertHasTag($filePath, "Unscanned");
            
            // Verify only one tag is present
            $this->assertTagCount($filePath, 1);
            
        } finally {
            // Clean up
            $this->executeDockerCommand("rm -f {$containerPath}");
        }
    }

    /**
     * Test unscanned job for test user
     */
    public function testUnscannedJobForTestUser(): void
    {
        $filename = "{$this->testUser}.unscanned.pup.exe";
        $pupFilePath = $this->folderPrefix . '/pup.exe';
        $containerPath = "/var/www/html/data/{$this->testUser}/files/{$filename}";
        
        if (!file_exists($pupFilePath)) {
            $this->markTestSkipped('PUP file not available');
        }
        
        try {
            // Copy PUP file directly to container (bypassing normal upload process)
            exec("docker cp {$pupFilePath} nextcloud-container:{$containerPath}", $output, $returnCode);
            if ($returnCode !== 0) {
                $this->markTestSkipped('Could not copy PUP file to container for testuser');
            }
            
            // Set proper ownership (this is done automatically in the BATS test by the docker exec user)
            $this->executeDockerCommand("chown www-data:www-data {$containerPath}");
            
            // Scan files for test user only
            $this->executeDockerCommand("php occ files:scan {$this->testUser}");
            
            // Tag unscanned files
            $this->executeDockerCommand("php occ gdatavaas:tag-unscanned");
            
            // Verify "Unscanned" tag is present
            $filePath = "{$this->testUser}/files/{$filename}";
            $this->assertHasTag($filePath, "Unscanned");
            
            // Verify only one tag is present
            $this->assertTagCount($filePath, 1);
            
        } finally {
            // Clean up
            $this->executeDockerCommand("rm -f {$containerPath}");
        }
    }

    /**
     * Creates a large file with specified size
     */
    private function createLargeFile(string $filePath, int $size): void
    {
        $chunkSize = 1024 * 1024; // 1MB chunks
        $handle = fopen($filePath, 'wb');
        
        if (!$handle) {
            throw new \RuntimeException("Cannot create file: {$filePath}");
        }
        
        try {
            $written = 0;
            $zeroChunk = str_repeat("\0", $chunkSize);
            
            while ($written < $size) {
                $remaining = $size - $written;
                $writeSize = min($chunkSize, $remaining);
                
                if ($writeSize < $chunkSize) {
                    $chunk = str_repeat("\0", $writeSize);
                } else {
                    $chunk = $zeroChunk;
                }
                
                $bytesWritten = fwrite($handle, $chunk);
                if ($bytesWritten === false) {
                    throw new \RuntimeException("Failed to write to file: {$filePath}");
                }
                
                $written += $bytesWritten;
            }
        } finally {
            fclose($handle);
        }
        
        echo "Created large file: {$filePath} ({$size} bytes)\n";
    }
}