<?php

use App\Core\Database;
use App\Services\ReportExportService;
use App\Services\ReportSnapshotService;

class ReportSnapshotServiceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfDbUnavailable();
        $db = Database::getInstance();
        $db->query(
            "SELECT COUNT(*) FROM grading_scales
             WHERE scale_name = 'السلم الخماسي' AND is_active = 1"
        );
        if ((int)$db->fetchColumn() === 0) {
            $this->markSkipped('السلم الخماسي النشط مطلوب لإنشاء نسخة التقرير.');
        }
    }

    public function testSnapshotHasSerialNumberAndRejectsUpdatesAndDeletes(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            [$employeeId, $userId] = $this->createEmployeeFixture($db);
            $service = new ReportSnapshotService($db);
            $snapshot = $service->createForEmployee($employeeId, $userId);

            $this->assertMatches('/^ER-[0-9]{8,}$/', $snapshot->report_number);
            $this->assertSame($employeeId, $snapshot->snapshot_data['employee']['id']);
            $this->assertSame('CD018 Snapshot Fixture', $snapshot->snapshot_data['employee']['full_name']);
            $this->assertSame($userId, $snapshot->snapshot_data['created_by']['user_id']);
            $this->assertSame($snapshot->report_number, $snapshot->snapshot_data['report_number']);
            $this->assertSame($snapshot->snapshot_data['created_by']['username'], $service->listForEmployee($employeeId)[0]->created_by_username);
            $this->assertSame((int)$snapshot->id, (int)$service->listForEmployee($employeeId)[0]->id);

            $updateRejected = false;
            try {
                $db->query('UPDATE report_snapshots SET snapshot_json = :snapshot_json WHERE id = :id');
                $db->bind(':snapshot_json', '{}');
                $db->bind(':id', (int)$snapshot->id);
                $db->execute();
            } catch (PDOException) {
                $updateRejected = true;
            }
            $this->assertTrue($updateRejected, 'The database must reject modifications to saved snapshots.');

            $deleteRejected = false;
            try {
                $db->query('DELETE FROM report_snapshots WHERE id = :id');
                $db->bind(':id', (int)$snapshot->id);
                $db->execute();
            } catch (PDOException) {
                $deleteRejected = true;
            }
            $this->assertTrue($deleteRejected, 'The database must reject deletion of saved snapshots.');

            $db->query('UPDATE employees SET full_name = :name WHERE id = :id');
            $db->bind(':name', 'Updated Employee Name');
            $db->bind(':id', $employeeId);
            $db->execute();

            $stillSaved = $service->findForEmployee($employeeId, (int)$snapshot->id);
            $this->assertSame('CD018 Snapshot Fixture', $stillSaved->snapshot_data['employee']['full_name']);

            $nextSnapshot = $service->createForEmployee($employeeId, $userId);
            $this->assertSame(
                (int)substr($snapshot->report_number, 3) + 1,
                (int)substr($nextSnapshot->report_number, 3)
            );
        } finally {
            $db->rollBack();
        }
    }

    public function testExportsUseTheSavedSnapshotForAllFormats(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            [$employeeId, $userId] = $this->createEmployeeFixture($db);
            $snapshot = (new ReportSnapshotService($db))->createForEmployee($employeeId, $userId);
            $exports = new ReportExportService();

            $pdf = $exports->render('pdf', $snapshot);
            $xlsx = $exports->render('xlsx', $snapshot);
            $docx = $exports->render('docx', $snapshot);

            $this->assertSame('application/pdf', $pdf['content_type']);
            $this->assertMatches('/^%PDF-/', $pdf['content']);
            $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $xlsx['content_type']);
            $this->assertMatches('/^PK/', $xlsx['content']);
            $this->assertSame('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $docx['content_type']);
            $this->assertMatches('/^PK/', $docx['content']);
            $this->assertStringContainsString($snapshot->report_number, $pdf['filename']);
            $this->assertStringContainsString($snapshot->report_number, $xlsx['filename']);
            $this->assertStringContainsString($snapshot->report_number, $docx['filename']);
        } finally {
            $db->rollBack();
        }
    }

    private function createEmployeeFixture(Database $db): array
    {
        $db->query('SELECT id FROM users ORDER BY id LIMIT 1');
        $userId = (int)$db->fetchColumn();
        $this->assertTrue($userId > 0, 'A user fixture is required.');

        $code = 'CD018-' . bin2hex(random_bytes(6));
        $db->query('INSERT INTO employees (emp_code, full_name) VALUES (:code, :name)');
        $db->bind(':code', $code);
        $db->bind(':name', 'CD018 Snapshot Fixture');
        $db->execute();

        return [(int)$db->lastInsertId(), $userId];
    }
}
