<?php
/**
 * اختبارات سلامة التسلسل الإداري (CD-009 — Fix 2).
 *
 * تغطي Employee::wouldCreateManagerCycle():
 *   - منع اختيار الموظف لنفسه كمدير (حلقة طول 1)
 *   - منع الحلقة المباشرة (A→B→A)
 *   - منع الحلقة الأطول (A→B→C→A)
 *   - قبول سلسلة صحيحة (بلا حلقة)
 *   - manager_id = null لا يُنشئ حلقة أبدًا
 *
 * كل الحالات تنشئ بيانات مؤقتة خاصة بها وتُنظَّف في tearDown().
 */

use App\Core\Database;
use App\Models\Employee;

class EmployeeManagerTest extends TestCase
{
    private string $prefix = 'cd009.mgr.';
    private array $tempIds = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfDbUnavailable();
        $this->tempIds = [];
    }

    public function tearDown(): void
    {
        $db = Database::getInstance();
        // إلغاء الربط أولًا (manager_id FK SET NULL) ثم الحذف بمعرّف صريح
        foreach (array_reverse($this->tempIds) as $id) {
            $db->query("UPDATE employees SET manager_id = NULL WHERE id = :id");
            $db->bind(':id', $id);
            $db->execute();
        }
        foreach (array_reverse($this->tempIds) as $id) {
            $db->query("DELETE FROM employees WHERE id = :id");
            $db->bind(':id', $id);
            $db->execute();
        }
        $this->tempIds = [];
        parent::tearDown();
    }

    /** ينشئ موظفًا مؤقتًا ويُرجع معرّفه. */
    private function createEmployee(string $tag, ?int $managerId = null): int
    {
        $db = Database::getInstance();
        $db->query("INSERT INTO employees (emp_code, full_name, manager_id, is_active)
                    VALUES (:code, :name, :mgr, 1)");
        $db->bind(':code', $this->prefix . $tag);
        $db->bind(':name', 'CD009 MGR ' . $tag);
        $db->bind(':mgr', $managerId);
        $db->execute();
        $id = (int)$db->lastInsertId();
        $this->tempIds[] = $id;
        return $id;
    }

    /** يضبط المدير المباشر لموظف. */
    private function setManager(int $id, ?int $managerId): void
    {
        $db = Database::getInstance();
        $db->query("UPDATE employees SET manager_id = :mgr WHERE id = :id");
        $db->bind(':mgr', $managerId);
        $db->bind(':id', $id);
        $db->execute();
    }

    /** يعيد قراءة manager_id من القاعدة. */
    private function managerOf(int $id): ?int
    {
        $db = Database::getInstance();
        $db->query("SELECT manager_id FROM employees WHERE id = :id");
        $db->bind(':id', $id);
        $row = $db->fetch();
        return ($row && $row->manager_id !== null) ? (int)$row->manager_id : null;
    }

    /** حلقة طول 1: الموظف مدير لنفسه. */
    public function testSelfManagerIsCycle(): void
    {
        $employee = new Employee();
        $a = $this->createEmployee('self');
        $this->assertTrue(
            $employee->wouldCreateManagerCycle($a, $a),
            'تعيين الموظف كمدير لنفسه يجب أن يُعدّ حلقة'
        );
    }

    /** حلقة مباشرة A→B→A. */
    public function testDirectCycleIsDetected(): void
    {
        $employee = new Employee();
        $a = $this->createEmployee('a');
        $b = $this->createEmployee('b', $a); // B مديره A

        // اقتراح جعل A تابعًا لـ B يُنشئ A→B→A
        $this->assertTrue(
            $employee->wouldCreateManagerCycle($a, $b),
            'الحلقة المباشرة A→B→A يجب أن تُكتشف'
        );
    }

    /** حلقة أطول A→B→C→A. */
    public function testLongerCycleIsDetected(): void
    {
        $employee = new Employee();
        $a = $this->createEmployee('a');
        $b = $this->createEmployee('b', $a); // B → A
        $c = $this->createEmployee('c', $b); // C → B → A

        // اقتراح جعل A تابعًا لـ C يُنشئ A→C→B→A
        $this->assertTrue(
            $employee->wouldCreateManagerCycle($a, $c),
            'الحلقة الأطول (طولها 3) يجب أن تُكتشف'
        );
    }

    /** سلسلة صحيحة: لا حلقة. */
    public function testValidChainIsAccepted(): void
    {
        $employee = new Employee();
        $a = $this->createEmployee('root');
        $b = $this->createEmployee('mid', $a);   // B → A
        $d = $this->createEmployee('leaf');       // بلا مدير

        // جعل D تابعًا لـ B: D→B→A — لا حلقة
        $this->assertFalse(
            $employee->wouldCreateManagerCycle($d, $b),
            'السلسلة الصحيحة D→B→A ليست حلقة'
        );
    }

    /** manager_id = null لا يُنشئ حلقة أبدًا. */
    public function testNullManagerIsNeverCycle(): void
    {
        $employee = new Employee();
        $a = $this->createEmployee('n');
        $this->assertFalse($employee->wouldCreateManagerCycle($a, null), 'null ليس حلقة');
        $this->assertFalse($employee->wouldCreateManagerCycle($a, 0), '0 (فارغ) ليس حلقة');
    }

    /**
     * التكامل: عند اختيار مدير يُنشئ حلقة، لا يجب أن تتغيّر بيانات الموظف.
     * يحاكي منطق الحارس في EmployeeController::update().
     */
    public function testGuardKeepsDataIntactWhenCycleRejected(): void
    {
        $employee = new Employee();
        $a = $this->createEmployee('ga');
        $b = $this->createEmployee('gb', $a); // B → A

        $before = $this->managerOf($b);
        $this->assertSame($a, $before, 'إعداد B→A فشل');

        // محاولة رفض: A مديرها سيكون B (حلقة) — الحارس يرفض قبل الكتابة
        if ($employee->wouldCreateManagerCycle($a, $b)) {
            // لا نكتب شيئًا (كما يفعل الكنترولر عند الرفض)
        } else {
            $this->setManager($a, $b);
        }

        // لم تتغيّر بيانات أي منهما بسبب الرفض
        $this->assertSame($before, $this->managerOf($b), 'تغيّرت بيانات B رغم رفض الحلقة');
        $this->assertNull($this->managerOf($a), 'كُتب manager_id لـ A رغم رفض الحلقة');
    }

    /** countDirectReports: يحصي المرؤوسين المباشرين فقط. */
    public function testCountDirectReports(): void
    {
        $employee = new Employee();
        $mgr = $this->createEmployee('cnt-mgr');
        $r1 = $this->createEmployee('cnt-r1', $mgr);
        $r2 = $this->createEmployee('cnt-r2', $mgr);
        $leaf = $this->createEmployee('cnt-leaf');

        $this->assertSame(2, $employee->countDirectReports($mgr), 'المدير له مرؤوسان مباشران');
        $this->assertSame(0, $employee->countDirectReports($leaf), 'الورقة بلا مرؤوسين');
    }

    /**
     * التكامل (Fix 3): مدير لديه مرؤوسون لا يُحذف ويبقى صفّه موجودًا.
     * يحاكي حارس EmployeeController::destroy().
     */
    public function testManagerWithSubordinatesIsNotDeleted(): void
    {
        $db = Database::getInstance();
        $employee = new Employee();
        $mgr = $this->createEmployee('del-mgr');
        $sub = $this->createEmployee('del-sub', $mgr);

        // محاكاة الحارس: إذا كان لديه مرؤوسون، لا نحذف
        if ($employee->countDirectReports($mgr) > 0) {
            // رفض الحذف — لا استدعاء لـ delete()
        } else {
            $employee->delete($mgr);
        }

        // المدير ما زال موجودًا
        $db->query("SELECT COUNT(*) FROM employees WHERE id = :id");
        $db->bind(':id', $mgr);
        $this->assertSame('1', (string)$db->fetchColumn(), 'حُذف مدير لديه مرؤوسون — الحارس فشل');

        // المرؤوس ما زال مرتبطًا بمديره (لم يُيتّم)
        $this->assertSame($mgr, $this->managerOf($sub), 'فُقد رابط المرؤوس بمديره');
    }

    /** موظف بلا مرؤوسين يمكن حذفه فعلًا. */
    public function testLeafEmployeeCanBeDeleted(): void
    {
        $db = Database::getInstance();
        $employee = new Employee();
        $leaf = $this->createEmployee('del-leaf');

        $this->assertSame(0, $employee->countDirectReports($leaf), 'الورقة بلا مرؤوسين مسبقًا');
        $this->assertTrue($employee->delete($leaf), 'حذف ورقة يجب أن ينجح');

        $db->query("SELECT COUNT(*) FROM employees WHERE id = :id");
        $db->bind(':id', $leaf);
        $this->assertSame('0', (string)$db->fetchColumn(), 'الورقة لم تُحذف');

        // أُزيل من قائمة التنظيف لأن الحذف تم هنا
        $this->tempIds = array_values(array_filter($this->tempIds, fn($x) => $x !== $leaf));
    }
}
