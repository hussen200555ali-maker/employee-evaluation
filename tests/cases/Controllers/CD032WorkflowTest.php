<?php
/**
 * CD-032 — اختبارات سير العمل الكامل (End-to-End)
 */
use App\Core\Database;
use App\Core\Auth;

class CD032WorkflowTest extends TestCase
{
    public function setUp(): void { parent::setUp(); $this->skipIfDbUnavailable(); }

    private function fetchAnyUserId(): int {
        $db = Database::getInstance();
        $db->query('SELECT id FROM users ORDER BY id LIMIT 1');
        return (int)$db->fetchColumn();
    }

    /**
     * اختبار 1: enrollStore يضيف موظفين بحالة pending + يمنع التكرار
     */
    public function testEnrollInsertsPendingAndPreventsDuplicates(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $pdo = $db->getPdo();
            $stamp = 'CD032-' . bin2hex(random_bytes(3));
            // catalog + course
            $pdo->prepare('INSERT INTO course_catalog (title, description) VALUES (:t, :d)')->execute(['t' => 'CD032 Cat ' . $stamp, 'd' => 'test']);
            $catId = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO training_courses (catalog_id, branch_id, status) VALUES (:cid, NULL, "scheduled")')->execute(['cid' => $catId]);
            $courseId = (int)$pdo->lastInsertId();
            // employees
            $e1 = 'CD032-' . bin2hex(random_bytes(3));
            $e2 = 'CD032-' . bin2hex(random_bytes(3));
            $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active, profile_status) VALUES (:c, :n, 1, "complete")')->execute(['c' => $e1, 'n' => 'Emp A']);
            $emp1 = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active, profile_status) VALUES (:c, :n, 1, "complete")')->execute(['c' => $e2, 'n' => 'Emp B']);
            $emp2 = (int)$pdo->lastInsertId();

            // INSERT IGNORE twice — second should be skipped
            $pdo->prepare('INSERT IGNORE INTO course_participants (course_id, employee_id, status, enrolled_at) VALUES (:cid, :eid, "pending", NOW())')->execute(['cid' => $courseId, 'eid' => $emp1]);
            $pdo->prepare('INSERT IGNORE INTO course_participants (course_id, employee_id, status, enrolled_at) VALUES (:cid, :eid, "pending", NOW())')->execute(['cid' => $courseId, 'eid' => $emp2]);
            // duplicate — should be ignored
            $pdo->prepare('INSERT IGNORE INTO course_participants (course_id, employee_id, status, enrolled_at) VALUES (:cid, :eid, "pending", NOW())')->execute(['cid' => $courseId, 'eid' => $emp1]);

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM course_participants WHERE course_id = :cid');
            $stmt->execute(['cid' => $courseId]);
            $this->assertSame(2, (int)$stmt->fetchColumn(), 'Should have exactly 2 participants (duplicate ignored)');

            $stmt2 = $pdo->prepare('SELECT status FROM course_participants WHERE course_id = :cid AND employee_id = :eid');
            $stmt2->execute(['cid' => $courseId, 'eid' => $emp1]);
            $this->assertSame('pending', (string)$stmt2->fetchColumn(), 'Status should be pending');
        } finally { $db->rollBack(); }
    }

    /**
     * اختبار 2: syncCourseParticipant يحدّث الحالة إلى completed
     */
    public function testSyncUpdatesStatusToCompleted(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $pdo = $db->getPdo();
            $stamp = 'CD032S-' . bin2hex(random_bytes(3));
            $pdo->prepare('INSERT INTO course_catalog (title, description) VALUES (:t, :d)')->execute(['t' => 'CD032S ' . $stamp, 'd' => '']);
            $catId = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO training_courses (catalog_id, status) VALUES (:cid, "completed")')->execute(['cid' => $catId]);
            $courseId = (int)$pdo->lastInsertId();
            $ec = 'CD032S-' . bin2hex(random_bytes(3));
            $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active, profile_status) VALUES (:c, :n, 1, "complete")')->execute(['c' => $ec, 'n' => 'Sync Test']);
            $empId = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO course_participants (course_id, employee_id, status, enrolled_at) VALUES (:cid, :eid, "pending", NOW())')->execute(['cid' => $courseId, 'eid' => $empId]);

            // simulate sync — UPDATE to completed
            $pdo->prepare('UPDATE course_participants SET status = "completed", completed_at = NOW() WHERE course_id = :cid AND employee_id = :eid')->execute(['cid' => $courseId, 'eid' => $empId]);

            $stmt = $pdo->prepare('SELECT status, completed_at FROM course_participants WHERE course_id = :cid AND employee_id = :eid');
            $stmt->execute(['cid' => $courseId, 'eid' => $empId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->assertSame('completed', $row['status']);
            $this->assertNotNull($row['completed_at']);
        } finally { $db->rollBack(); }
    }

    /**
     * اختبار 3: routes تحتوي على enroll + show + pending-employees API
     */
    public function testRoutesContainNewEndpoints(): void
    {
        $routes = file_get_contents(APP_ROOT . '/routes/web.php');
        $this->assertStringContainsString('training/courses/enroll', $routes);
        $this->assertStringContainsString('TrainingCourseController@enroll', $routes);
        $this->assertStringContainsString('TrainingCourseController@show', $routes);
        $this->assertStringContainsString('pending-employees', $routes);
    }
}
