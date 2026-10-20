<?php
/**
 * اختبارات متحكّم المستخدمين (App\Controllers\Admin\UserController) — OOS-13.
 *
 * تغطي تسجيل تدقيق فشل تحديث الدور:
 *   - فشل updateRole (role_id غير موجود) يجب أن يكتب USER_ROLE_UPDATE_FAILED،
 *     وبألا يكتب USER_ROLE_UPDATE.
 *
 * المتحكّم ينتهي بـ exit()، لذا يُستدعى في عملية فرعية (نمط AuthTest).
 * كل البيانات مؤقتة وتُنظَّف في tearDown().
 */

class UserControllerTest extends TestCase
{
    private string $prefix = 'oos13.uctest';
    private array $seededUserIds = [];
    private array $seededRoleIds = [];

    public function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    private function testDb(): \PDO
    {
        return new \PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    /** فاعل بدور Admin؛ صلاحياته تُحمَّل من البيانات (OOS-18: بلا تجاوز)، ويُعاد معرّفه. */
    private function seedActorAdmin(): int
    {
        $db = $this->testDb();
        $name = $this->prefix . '.actor.' . uniqid();
        $db->prepare('INSERT INTO users (username, email, password_hash, status, must_change_password)
                      VALUES (?, ?, ?, \'active\', 0)')
            ->execute([$name, $name . '@test.local', password_hash('Oos13#Actor1', PASSWORD_DEFAULT)]);
        $id = (int)$db->lastInsertId();
        $db->prepare("INSERT INTO user_roles (user_id, role_id)
                      VALUES (?, (SELECT id FROM roles WHERE name = 'Admin' AND is_system_role = 1 LIMIT 1))")
            ->execute([$id]);
        $this->seededUserIds[] = $id;
        return $id;
    }

    /** مستخدم هدف نشط بلا دور. */
    private function seedTargetUser(): int
    {
        $db = $this->testDb();
        $name = $this->prefix . '.target.' . uniqid();
        $db->prepare('INSERT INTO users (username, email, password_hash, status, must_change_password)
                      VALUES (?, ?, ?, \'active\', 0)')
            ->execute([$name, $name . '@test.local', password_hash('Oos13#Target1', PASSWORD_DEFAULT)]);
        $id = (int)$db->lastInsertId();
        $this->seededUserIds[] = $id;
        return $id;
    }

    private function countActionFor(string $action, int $entityId): int
    {
        $db = $this->testDb();
        $st = $db->prepare("SELECT COUNT(*) FROM audit_logs WHERE action = ? AND entity_type = 'users' AND entity_id = ?");
        $st->execute([$action, $entityId]);
        return (int)$st->fetchColumn();
    }

    private function cleanup(): void
    {
        if ($this->seededUserIds === [] && $this->seededRoleIds === [] || !db_available()) {
            $this->seededUserIds = [];
            $this->seededRoleIds = [];
            return;
        }
        try {
            $db = $this->testDb();
            $ids = $this->seededUserIds;
            if ($ids !== []) {
                $ip = implode(',', array_fill(0, count($ids), '?'));
                $db->prepare("DELETE FROM audit_logs WHERE entity_type = 'users' AND entity_id IN ({$ip})")->execute($ids);
                $db->prepare("DELETE FROM notifications WHERE user_id IN ({$ip})")->execute($ids);
                $db->prepare("DELETE FROM csrf_tokens WHERE user_id IN ({$ip})")->execute($ids);
                $db->prepare("DELETE FROM user_roles WHERE user_id IN ({$ip})")->execute($ids);
                $db->prepare("DELETE FROM users WHERE id IN ({$ip})")->execute($ids);
            }
            if ($this->seededRoleIds !== []) {
                $rp = implode(',', array_fill(0, count($this->seededRoleIds), '?'));
                $db->prepare("DELETE FROM role_permissions WHERE role_id IN ({$rp})")->execute($this->seededRoleIds);
                $db->prepare("DELETE FROM user_roles WHERE role_id IN ({$rp})")->execute($this->seededRoleIds);
                $db->prepare("DELETE FROM roles WHERE id IN ({$rp})")->execute($this->seededRoleIds);
            }
        } catch (\Throwable $e) {
            // التنظيف لا يُسقط الاختبار — لكنه يُذكر
            fwrite(STDERR, 'UserControllerTest cleanup: ' . $e->getMessage() . PHP_EOL);
        }
        $this->seededUserIds = [];
        $this->seededRoleIds = [];
    }

    /**
     * OOS-13 — الاختبار الحاسم:
     * role_id غير موجود ⇒ المتحكّم يكتب USER_ROLE_UPDATE_FAILED (1) بالتفاصيل،
     * ولا يكتب USER_ROLE_UPDATE.
     */
    public function testUpdateRoleFailureAuditLog(): void
    {
        $this->skipIfDbUnavailable();

        $actor  = $this->seedActorAdmin();
        $target = $this->seedTargetUser();

        $failedBefore = $this->countActionFor('USER_ROLE_UPDATE_FAILED', $target);
        $this->assertSame(0, $failedBefore, 'لا يجب أن يوجد سجل فشل قبل المحاولة');

        // عملية فرعية: استدعاء المتحكّم كما يفعل المسار الحقيقي (ينتهي بـ exit)
        $script = $this->tempFile('php');
        $boot = var_export(APP_ROOT . '/tests/bootstrap.php', true);
        $a = var_export($actor, true);
        $t = var_export($target, true);
        file_put_contents($script, '<?php
require ' . $boot . ';
$_SESSION = [];
$_SESSION["user_id"]    = ' . $a . ';
$_SESSION["user_roles"] = ["Admin"];
\App\Core\Auth::refreshSessionPermissions();
$_SERVER["REQUEST_METHOD"] = "POST";
$_SERVER["REMOTE_ADDR"]    = "127.0.0.1";
$_POST = ["user_id" => ' . $t . ', "role_id" => 999];
(new \App\Controllers\Admin\UserController())->updateRole();
');
        $res = $this->runCli([$script]);
        $this->assertSame(0, $res['exit'], 'العملية الفرعية فشلت: ' . $res['stderr']);

        // (أ) سجل الفشل كُتب مرة واحدة
        $failedAfter = $this->countActionFor('USER_ROLE_UPDATE_FAILED', $target);
        $this->assertSame(1, $failedAfter, 'متوقع سجل USER_ROLE_UPDATE_FAILED واحد عند فشل updateRole');

        // (ب) التفاصيل تحمل role_id الصحيح
        $db = $this->testDb();
        $st = $db->prepare("SELECT details FROM audit_logs WHERE action = 'USER_ROLE_UPDATE_FAILED' AND entity_id = ? ORDER BY id DESC LIMIT 1");
        $st->execute([$target]);
        $details = (string)$st->fetchColumn();
        $decoded = json_decode($details, true);
        $this->assertIsArray($decoded, 'تفاصيل سجل الفشل ليست JSON صالحًا');
        $this->assertSame(999, (int)($decoded['role_id'] ?? 0), 'role_id في التفاصيل غير صحيح');

        // (ج) لا يُكتب USER_ROLE_UPDATE عند الفشل
        $updateAfter = $this->countActionFor('USER_ROLE_UPDATE', $target);
        $this->assertSame(0, $updateAfter, 'لا يجب تسجيل USER_ROLE_UPDATE عند فشل التحديث');
    }
}
