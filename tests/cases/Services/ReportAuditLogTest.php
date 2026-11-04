<?php

use App\Core\Database;
use App\Services\ReportSnapshotService;

class ReportAuditLogTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfDbUnavailable();
    }

    public function testReportGenerationLogsAuditEvent(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $previousSession = $_SESSION;

        try {
            $db->query('SELECT id FROM employees ORDER BY id LIMIT 1');
            $employeeId = (int)$db->fetchColumn();
            $db->query('SELECT id FROM users ORDER BY id LIMIT 1');
            $userId = (int)$db->fetchColumn();
            $this->assertTrue($employeeId > 0 && $userId > 0, 'Employee and user fixtures are required.');
            $_SESSION['user_id'] = $userId;

            $db->query("SELECT COUNT(*) FROM audit_logs WHERE action = 'REPORT_ISSUED'");
            $before = (int)$db->fetchColumn();

            $snapshot = (new ReportSnapshotService($db))->createForEmployee($employeeId, $userId);

            $db->query(
                "SELECT COUNT(*) FROM audit_logs
                 WHERE action = 'REPORT_ISSUED'
                   AND entity_type = 'report_snapshots'
                   AND entity_id = :snapshot_id"
            );
            $db->bind(':snapshot_id', (int)$snapshot->id);
            $this->assertSame(1, (int)$db->fetchColumn());

            $db->query("SELECT COUNT(*) FROM audit_logs WHERE action = 'REPORT_ISSUED'");
            $this->assertSame($before + 1, (int)$db->fetchColumn());
        } finally {
            $_SESSION = $previousSession;
            $db->rollBack();
        }
    }
}
