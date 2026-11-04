<?php

use App\Core\Database;
use App\Controllers\ReportsController;
use App\Services\ReportSnapshotService;

class ReportsArchiveTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfDbUnavailable();
    }

    public function testReportsArchiveShowsSnapshots(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $previousSession = $_SESSION;
        $outputLevel = ob_get_level();

        try {
            $db->query('SELECT id FROM users ORDER BY id LIMIT 1');
            $userId = (int)$db->fetchColumn();
            $this->assertTrue($userId > 0, 'A user fixture is required.');

            $employeeCode = 'CD035-ARCH-' . bin2hex(random_bytes(4));
            $db->query('INSERT INTO employees (emp_code, full_name) VALUES (:code, :name)');
            $db->bind(':code', $employeeCode);
            $db->bind(':name', 'Archive Report Fixture');
            $db->execute();
            $employeeId = (int)$db->lastInsertId();

            $_SESSION['user_id'] = $userId;
            $_SESSION['user_permissions'] = ['employees.profile'];
            $_SESSION['user_object'] = (object)['full_name' => 'Archive Test User', 'username' => 'archive-test'];

            $snapshot = (new ReportSnapshotService($db))->createForEmployee($employeeId, $userId);
            ob_start();
            (new ReportsController())->archive();
            $html = (string)ob_get_clean();

            $this->assertStringContainsString($snapshot->report_number, $html);
            $this->assertStringContainsString('Archive Report Fixture', $html);
        } finally {
            while (ob_get_level() > $outputLevel) {
                ob_end_clean();
            }
            $_SESSION = $previousSession;
            $db->rollBack();
        }
    }
}
