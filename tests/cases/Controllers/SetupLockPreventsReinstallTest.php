<?php

class SetupLockPreventsReinstallTest extends TestCase
{
    public function testInstalledLockBlocksTheSetupWizard(): void
    {
        $temporaryRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . 'employee-evaluation-setup-lock-' . bin2hex(random_bytes(6));
        $publicDirectory = $temporaryRoot . DIRECTORY_SEPARATOR . 'public';
        $configDirectory = $temporaryRoot . DIRECTORY_SEPARATOR . 'config';
        $scriptPath = $publicDirectory . DIRECTORY_SEPARATOR . 'setup.php';
        $lockPath = $configDirectory . DIRECTORY_SEPARATOR . 'installed.lock';

        mkdir($publicDirectory, 0700, true);
        mkdir($configDirectory, 0700, true);

        try {
            $source = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public'
                . DIRECTORY_SEPARATOR . 'setup.php';
            $this->assertTrue(copy($source, $scriptPath), 'Setup script should be copied to the isolated fixture.');
            $this->assertTrue(file_put_contents($lockPath, "installed test\n") !== false);

            $result = $this->runCli([$scriptPath]);

            $this->assertSame(0, $result['exit']);
            $this->assertStringContainsString('معالج التثبيت مغلق', $result['stdout']);
            $this->assertFalse(str_contains($result['stdout'], '<form'));
        } finally {
            if (is_file($lockPath)) {
                unlink($lockPath);
            }
            if (is_file($scriptPath)) {
                unlink($scriptPath);
            }
            if (is_dir($configDirectory)) {
                rmdir($configDirectory);
            }
            if (is_dir($publicDirectory)) {
                rmdir($publicDirectory);
            }
            if (is_dir($temporaryRoot)) {
                rmdir($temporaryRoot);
            }
        }
    }
}
