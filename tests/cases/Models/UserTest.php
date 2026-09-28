<?php
/**
 * اختبارات مودل المستخدم (App\Models\User) — بند 9 (CD-006).
 *
 * تغطي ذرّية استبدال الأدوار في updateRole():
 *   - فشل الإدراج بعد نجاح الحذف يجب أن يتراجع ويُبقي الدور القديم.
 *
 * كل الحالات تنشئ بيانات مؤقتة خاصة بها وتُنظَّف في tearDown().
 */

use App\Core\Database;
use App\Models\User;

class UserTest extends TestCase
{
    private string $prefix = 'a2i9.utest';
    private int $tempRoleId = 0;
    private int $tempUserId = 0;

    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfDbUnavailable();

        $db = Database::getInstance();

        // دور مؤقت يمنح المستخدم دورًا حقيقيًا صالحًا
        $db->query("INSERT INTO roles (name, description, is_system_role) VALUES (:name, :desc, 0)");
        $db->bind(':name', $this->prefix . '.role');
        $db->bind(':desc', 'temporary role for item 9 unit test');
        $db->execute();
        $this->tempRoleId = (int)$db->lastInsertId();

        // مستخدم مؤقت بدون دور (سنمنحه الدور عبر updateRole)
        $db->query("INSERT INTO users (username, email, password_hash, status, must_change_password)
                    VALUES (:username, :email, :hash, 'active', 0)");
        $db->bind(':username', $this->prefix . '.user');
        $db->bind(':email', $this->prefix . '.user@example.local');
        $db->bind(':hash', password_hash('A2Item9Test#2026', PASSWORD_DEFAULT));
        $db->execute();
        $this->tempUserId = (int)$db->lastInsertId();
    }

    public function tearDown(): void
    {
        // الحذف بمعرّف صريح: يتفادى trigger الذي يمنع DELETE ... WHERE id IN (SELECT ... FROM users)
        $db = Database::getInstance();
        if ($this->tempUserId > 0) {
            $db->query("DELETE FROM user_roles WHERE user_id = :uid");
            $db->bind(':uid', $this->tempUserId);
            $db->execute();
            $db->query("DELETE FROM users WHERE id = :uid");
            $db->bind(':uid', $this->tempUserId);
            $db->execute();
        }
        if ($this->tempRoleId > 0) {
            $db->query("DELETE FROM role_permissions WHERE role_id = :rid");
            $db->bind(':rid', $this->tempRoleId);
            $db->execute();
            $db->query("DELETE FROM roles WHERE id = :rid");
            $db->bind(':rid', $this->tempRoleId);
            $db->execute();
        }
        $this->tempUserId = 0;
        $this->tempRoleId = 0;

        parent::tearDown();
    }

    /** قراءة دور المستخدم الحالي من القاعدة مباشرة. */
    private function currentRoleId(int $userId): ?int
    {
        $db = Database::getInstance();
        $db->query("SELECT role_id FROM user_roles WHERE user_id = :uid LIMIT 1");
        $db->bind(':uid', $userId);
        $row = $db->fetch();
        return $row ? (int)$row->role_id : null;
    }

    /**
     * بند 9 — الاختبار الحاسم:
     * دور غير موجود (999) ⇒ الحذف ينجح ثم الإدراج يفشل بـFK ⇒ rollback ⇒
     * (أ) الدالة تُرجع false بدل رمي استثناء، و(ب) المستخدم يحتفظ بدوره القديم.
     */
    public function testUpdateRoleRollsBackOnInvalidRole(): void
    {
        $this->skipIfDbUnavailable();
        $user = new User();

        // 1) الحالة الابتدائية: الدور القديم = الدور المؤقت
        $this->assertTrue($user->updateRole($this->tempUserId, $this->tempRoleId), 'إعداد الدور الابتدائي فشل');
        $roleBefore = $this->currentRoleId($this->tempUserId);
        $this->assertSame($this->tempRoleId, $roleBefore, 'لم يُضبط الدور الابتدائي');

        // 2) محاولة تغييره إلى دور غير موجود في جدول roles
        $invalidRoleId = 999;
        $this->assertFalse(
            $user->updateRole($this->tempUserId, $invalidRoleId),
            'كان متوقعًا false عند تمرير role_id غير موجود — لا استثناء'
        );

        // 3) الفرق الجوهري: الدور القديم ما زال موجودًا (التراجع نجح)
        $roleAfter = $this->currentRoleId($this->tempUserId);
        $this->assertSame(
            $roleBefore,
            $roleAfter,
            'التراجع لم يُرجع الدور القديم — المستخدم فقد دوره'
        );
        $this->assertNotNull($roleAfter, 'المستخدم أصبح بلا دور — انكسرت الذرّية');

        // 4) لا صف متروك يحمل الدور غير الموجود
        $db = Database::getInstance();
        $db->query("SELECT COUNT(*) FROM user_roles WHERE user_id = :uid AND role_id = :rid");
        $db->bind(':uid', $this->tempUserId);
        $db->bind(':rid', $invalidRoleId);
        $this->assertSame('0', (string)$db->fetchColumn(), 'توجد صفّ بدور غير موجود');
    }

    /** المسار السليم: تغيير الدور ينجح ويُحفظ. */
    public function testUpdateRoleSucceedsForValidRole(): void
    {
        $this->skipIfDbUnavailable();
        $user = new User();

        // دور ثانٍ صالح موجود فعلًا في النظام
        $db = Database::getInstance();
        $db->query("SELECT id FROM roles WHERE id <> :rid ORDER BY id LIMIT 1");
        $db->bind(':rid', $this->tempRoleId);
        $validRoleId = (int)$db->fetchColumn();
        $this->assertGreaterThan(0, $validRoleId, 'لا يوجد دور صالح للاختبار');

        $this->assertTrue($user->updateRole($this->tempUserId, $this->tempRoleId), 'فشل الإعداد');
        $this->assertTrue(
            $user->updateRole($this->tempUserId, $validRoleId),
            'تغيير الدور إلى دور موجود يجب أن ينجح'
        );
        $this->assertSame($validRoleId, $this->currentRoleId($this->tempUserId), 'الدور الجديد لم يُحفظ');
    }

    /** استبدال كل الأدوار: من مستخدم له دوران يصبح له دور واحد. */
    public function testUpdateRoleReplacesAllPreviousRoles(): void
    {
        $this->skipIfDbUnavailable();
        $user = new User();

        $db = Database::getInstance();
        $db->query("SELECT id FROM roles WHERE id <> :rid ORDER BY id LIMIT 1");
        $db->bind(':rid', $this->tempRoleId);
        $otherRoleId = (int)$db->fetchColumn();
        $this->assertGreaterThan(0, $otherRoleId, 'لا يوجد دور ثانٍ للاختبار');

        // منح دورين يدويًا (سلوك ما قبل البند 9 مسموح به على مستوى الجدول)
        $db->query("INSERT INTO user_roles (user_id, role_id) VALUES (:uid, :rid)");
        $db->bind(':uid', $this->tempUserId);
        $db->bind(':rid', $this->tempRoleId);
        $db->execute();
        $db->query("INSERT INTO user_roles (user_id, role_id) VALUES (:uid, :rid)");
        $db->bind(':uid', $this->tempUserId);
        $db->bind(':rid', $otherRoleId);
        $db->execute();

        $this->assertTrue($user->updateRole($this->tempUserId, $this->tempRoleId), 'الاستبدال فشل');

        $db->query("SELECT COUNT(*) FROM user_roles WHERE user_id = :uid");
        $db->bind(':uid', $this->tempUserId);
        $this->assertSame('1', (string)$db->fetchColumn(), 'لم يُستبدل الدور القديم بل أُضيف ثانٍ');
    }
}
