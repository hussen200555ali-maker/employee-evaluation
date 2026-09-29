<?php
/**
 * اختبارات المصادقة والصلاحيات (app/Core/Auth.php)
 */

use App\Core\Auth;

class AuthTest extends TestCase
{
    private const TEST_PASSWORD = 'CorrectP@ss123!';

    /** أسماء مستخدمين مؤقتين بُذرت خلال الاختبارات — تُنظَّف في tearDown */
    private array $seededUsernames = [];

    /** معرّفات أدوار مؤقتة بُذرت خلال الاختبارات — تُنظَّف في tearDown */
    private array $seededRoles = [];

    public function tearDown(): void
    {
        $this->cleanupStatusUsers();
        $this->cleanupSeededRoles();
        parent::tearDown();
    }

    // ======================================================================
    // أدوات بند 1 (فحص status) — بذر/تنظيف بيانات اختبارية مؤقتة
    // ======================================================================

    private function testDb(): \PDO
    {
        return new \PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    private function seedStatusUser(string $username, string $status): int
    {
        $db = $this->testDb();
        $db->prepare('INSERT INTO users (username, email, password_hash, status) VALUES (?, ?, ?, ?)')
            ->execute([$username, $username, password_hash(self::TEST_PASSWORD, PASSWORD_DEFAULT), $status]);
        // البند 11-b: التسجيل هنا (لا عند مواضع الاستدعاء) يغطي كل مواضع البذر دفعةً واحدة.
        $this->seededUsernames[] = $username;
        return (int)$db->lastInsertId();
    }

    private function seedAdminUser(string $username): int
    {
        $id = $this->seedStatusUser($username, 'active');
        // بند 14: الدور يسند عبر البحث الديناميكي عن دور Admin النظامي (بدون معرّف صريح)
        $this->testDb()->prepare(
            "INSERT INTO user_roles (user_id, role_id)
             VALUES (?, (SELECT id FROM roles WHERE name = 'Admin' AND is_system_role = 1 LIMIT 1))"
        )->execute([$id]);
        return $id;
    }

    private function cleanupStatusUsers(): void
    {
        $usernames = $this->seededUsernames;
        if ($usernames === [] || !db_available()) {
            $this->seededUsernames = [];
            return;
        }
        try {
            $db = $this->testDb();
            $ph = implode(',', array_fill(0, count($usernames), '?'));

            // البند 11-b — الجذر الحقيقي للتسرّب (مثبت بالخطأ 1442):
            // كان التنظيف يحذف user_roles بجملة تستعلم users في الوقت نفسه
            // (… WHERE user_id IN (SELECT id FROM users …))، فتلتقط
            // cd004_trg_user_roles_ad الذي ينفّذ UPDATE users فيتعارض معه
            // MySQL ويُطلق 1442 «Can't update table 'users' …»، فيتوقف
            // التنظيف عند ذاك السطر ولا يصل حذف المستخدمين إطلاقًا.
            // الحل: تُحلّ المعرّفات أولًا في استعلام مستقل، ثم تُحذف كل
            // الجداول بقائمة معرّفات literals بلا أي استعلام فرعي على users.
            $st = $db->prepare("SELECT id FROM users WHERE username IN ({$ph})");
            $st->execute($usernames);
            $ids = array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN));
            if ($ids === []) {
                $this->seededUsernames = [];
                return;
            }
            $ip = implode(',', array_fill(0, count($ids), '?'));

            $db->prepare("DELETE FROM audit_logs WHERE entity_type = 'users' AND entity_id IN ({$ip})")
               ->execute($ids);
            $db->prepare("DELETE FROM notifications WHERE user_id IN ({$ip})")
               ->execute($ids);
            $delNotif = $db->prepare('DELETE FROM notifications WHERE title LIKE ?');
            foreach ($usernames as $u) {
                $delNotif->execute(['%' . $u . '%']);
            }
            $db->prepare("DELETE FROM login_attempts WHERE username IN ({$ph})")->execute($usernames);
            $db->prepare("DELETE FROM user_roles WHERE user_id IN ({$ip})")
               ->execute($ids);
            // الاعتماد على CASCADE الحقيقي بعد إزالة تجاوز تعطيل فحوص المفاتيح:
            // notifications/csrf_tokens تُحذف تلقائيًا، وأي مرجع غير متوقَّع
            // يظهر كفشل صريح بدل أن يُنتج تلفًا صامتًا.
            $db->prepare("DELETE FROM users WHERE id IN ({$ip})")
               ->execute($ids);
        } catch (\Throwable $e) {
            // التنظيف ثانوي — لا يُفشِل الاختبار
        }
        $this->seededUsernames = [];
    }

    /** حذف الأدوار المؤقتة لبند 8 (حذف الدور يمحو role_permissions عبر FK cascade). */
    private function cleanupSeededRoles(): void
    {
        if ($this->seededRoles === [] || !db_available()) {
            return;
        }
        try {
            $db = $this->testDb();
            $ph = implode(',', array_fill(0, count($this->seededRoles), '?'));
            $db->prepare("DELETE FROM user_roles WHERE role_id IN ({$ph})")->execute($this->seededRoles);
            $db->prepare("DELETE FROM roles WHERE id IN ({$ph})")->execute($this->seededRoles);
        } catch (\Throwable $e) {
            // التنظيف ثانوي — لا يُفشِل الاختبار
        }
        $this->seededRoles = [];
    }

    // ======================================================================
    // أدوات بند 8 — نسخة الصلاحيات (permissions_version)
    // ======================================================================

    private function permissionsVersionOfId(int $userId): int
    {
        $st = $this->testDb()->prepare('SELECT permissions_version FROM users WHERE id = ?');
        $st->execute([$userId]);
        return (int)$st->fetchColumn();
    }

    private function roleIdByName(string $name): ?int
    {
        $st = $this->testDb()->prepare('SELECT id FROM roles WHERE name = ? LIMIT 1');
        $st->execute([$name]);
        $id = $st->fetchColumn();
        return $id === false ? null : (int)$id;
    }

    private function seedRole(string $name): int
    {
        $db = $this->testDb();
        $db->prepare('INSERT INTO roles (name, description, is_system_role) VALUES (?, ?, 0)')
           ->execute([$name, 'دور مؤقت — اختبار البند 8']);
        $id = (int)$db->lastInsertId();
        $this->seededRoles[] = $id;
        return $id;
    }

    private function auditExists(int $userId, string $action): bool
    {
        $db = $this->testDb();
        $st = $db->prepare("SELECT COUNT(*) FROM audit_logs WHERE entity_type = 'users' AND entity_id = ? AND action = ?");
        $st->execute([$userId, $action]);
        return (int)$st->fetchColumn() > 0;
    }

    private function notificationForUser(int $userId, string $usernameNeedle): bool
    {
        $db = $this->testDb();
        $st = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND title LIKE ?');
        $st->execute([$userId, '%' . $usernameNeedle . '%']);
        return (int)$st->fetchColumn() > 0;
    }

    private function loginScript(string $username): string
    {
        $script = $this->tempFile('php');
        $boot = var_export(APP_ROOT . '/tests/bootstrap.php', true);
        $u = var_export($username, true);
        $p = var_export(self::TEST_PASSWORD, true);
        file_put_contents($script, '<?php
require ' . $boot . ';
$_SERVER["REQUEST_METHOD"] = "POST";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
$_POST["username"] = ' . $u . ';
$_POST["password"] = ' . $p . ';
register_shutdown_function(function () {
    echo "SESSION_USER_ID=" . ($_SESSION["user_id"] ?? "none") . PHP_EOL;
    echo "FLASH=" . json_encode($_SESSION[\App\Core\Flash::FLASH_KEY] ?? [], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    echo "LOCATION=" . (headers_sent() ? "sent" : implode("|", headers_list())) . PHP_EOL;
    echo "PERMS_VERSION=" . ($_SESSION["permissions_version"] ?? "none") . PHP_EOL;
    echo "PERMS_CHECK_AT=" . ($_SESSION["permissions_check_at"] ?? "none") . PHP_EOL;
    echo "ROLES=" . json_encode($_SESSION["user_roles"] ?? [], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    echo "IS_ADMIN=" . (\App\Core\Auth::isAdmin() ? "1" : "0") . PHP_EOL;
});
try {
    (new \App\Controllers\AuthController())->login();
} catch (\Throwable $e) {
    echo "ERROR=" . $e->getMessage() . PHP_EOL;
}
echo "SURVIVED_LOGIN";
');
        return $script;
    }

    public function testCheckReturnsFalseWithoutSessionUser(): void
    {
        $_SESSION = [];
        $this->assertFalse(Auth::check());
    }

    public function testCheckReturnsTrueWithSessionUser(): void
    {
        $_SESSION['user_id'] = 5;
        $this->assertTrue(Auth::check());
    }

    public function testIdReturnsNullWithoutUser(): void
    {
        $_SESSION = [];
        $this->assertNull(Auth::id());
    }

    public function testIdReturnsUserId(): void
    {
        $_SESSION['user_id'] = 7;
        $this->assertSame(7, Auth::id());
    }

    public function testHasRoleReturnsFalseWhenLoggedOut(): void
    {
        $_SESSION = [];
        $this->assertFalse(Auth::hasRole('Admin'));
    }

    public function testHasRoleReturnsFalseWhenNoRolesInSession(): void
    {
        $_SESSION['user_id'] = 1;
        $this->assertFalse(Auth::hasRole('Admin'));
    }

    public function testHasRoleReturnsTrueWhenRolePresent(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['Admin', 'Manager'];
        $this->assertTrue(Auth::hasRole('Manager'));
        $this->assertFalse(Auth::hasRole('Employee'));
    }

    public function testIsAdmin(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['Admin'];
        $this->assertTrue(Auth::isAdmin());

        $_SESSION['user_roles'] = ['User'];
        $this->assertFalse(Auth::isAdmin());
    }

    public function testUserHasPermissionRejectsLoggedOutUsers(): void
    {
        $_SESSION = [];
        $this->assertFalse(Auth::userHasPermission('employees.view'));
    }

    public function testUserHasPermissionAdminBypass(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['Admin'];
        $this->assertTrue(Auth::userHasPermission('any.permission.xyz'));
    }

    public function testUserHasPermissionExactMatch(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['User'];
        $_SESSION['user_permissions'] = ['employees.view', 'reports.view.all'];
        $this->assertTrue(Auth::userHasPermission('employees.view'));
        $this->assertFalse(Auth::userHasPermission('employees.create'));
    }

    public function testGenerateCsrfTokenReturnsHexToken(): void
    {
        $this->skipIfDbUnavailable();
        $_SESSION['user_id'] = 1;
        $token = Auth::generateCsrfToken();
        $this->assertMatches('/^[0-9a-f]{64}$/', $token);
        $this->assertSame($token, $_SESSION['csrf_token'] ?? null);
        $this->assertNotEmpty($_SESSION['csrf_token_expires'] ?? null);
    }

    public function testValidateCsrfTokenWithValidToken(): void
    {
        $this->skipIfDbUnavailable();
        $_SESSION['user_id'] = 1;
        $token = Auth::generateCsrfToken();
        $this->assertTrue(Auth::validateCsrfToken($token));
    }

    public function testValidateCsrfTokenWithWrongToken(): void
    {
        $this->skipIfDbUnavailable();
        $_SESSION['user_id'] = 1;
        Auth::generateCsrfToken();
        $this->assertFalse(Auth::validateCsrfToken('0000000000000000000000000000000000000000000000000000000000000000'));
    }

    public function testValidateCsrfTokenWithExpiredToken(): void
    {
        $this->skipIfDbUnavailable();
        $_SESSION['user_id'] = 1;
        $_SESSION['csrf_token'] = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6';
        $_SESSION['csrf_token_expires'] = date('Y-m-d H:i:s', time() - 3600);
        $this->assertFalse(Auth::validateCsrfToken($_SESSION['csrf_token']));
    }

    public function testEnforceCsrfTokenSkipsGetRequests(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['url'] = 'dashboard';
        Auth::enforceCsrfToken();
        $this->assertTrue(true);
    }

    public function testEnforceCsrfTokenAllowsLoginPostWithValidToken(): void
    {
        $this->skipIfDbUnavailable();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['url'] = 'login';
        $_SESSION['user_id'] = 1;
        $token = Auth::generateCsrfToken();
        $_POST['_token'] = $token;
        Auth::enforceCsrfToken();
        $this->assertTrue(true);
    }

    public function testEnforceCsrfTokenRejectsLoginPostWithoutToken(): void
    {
        $this->skipIfDbUnavailable();
        $script = $this->tempFile('php');
        file_put_contents($script, '<?php
require ' . var_export(APP_ROOT . '/tests/bootstrap.php', true) . ';
$_SESSION["user_id"] = 1;
$_SERVER["REQUEST_METHOD"] = "POST";
$_GET["url"] = "login";
\App\Core\Auth::enforceCsrfToken();
echo "SURVIVED";
');
        $result = $this->runCli([$script]);
        $this->assertTrue($result['exit'] === 0, 'تم رفض POST /login بلا توكن عبر exit داخل enforceCsrfToken');
        $this->assertFalse(str_contains($result['stdout'], 'SURVIVED'), 'يجب ألا يصل التنفيذ بعد الرفض');
    }

    public function testLoginDelaySecondsProgression(): void
    {
        $this->assertSame(1, Auth::loginDelaySeconds(0));
        $this->assertSame(2, Auth::loginDelaySeconds(1));
        $this->assertSame(4, Auth::loginDelaySeconds(2));
        $this->assertSame(8, Auth::loginDelaySeconds(3));
        $this->assertSame(8, Auth::loginDelaySeconds(4));
        $this->assertSame(8, Auth::loginDelaySeconds(8));
    }

    public function testCountFailuresAndLockAfterThreshold(): void
    {
        $this->skipIfDbUnavailable();
        $username = 'locktest.progress@example.test';
        Auth::clearLoginAttempts($username);
        $this->assertSame(0, Auth::countLoginFailures($username));
        $this->assertFalse(Auth::isLoginLocked($username));

        for ($i = 0; $i < Auth::MAX_LOGIN_ATTEMPTS; $i++) {
            Auth::logLoginAttempt($username, false);
        }

        $this->assertSame(Auth::MAX_LOGIN_ATTEMPTS, Auth::countLoginFailures($username));
        $this->assertTrue(Auth::isLoginLocked($username));

        $this->assertTrue(Auth::clearLoginAttempts($username) === null);
        $this->assertSame(0, Auth::countLoginFailures($username));
        $this->assertFalse(Auth::isLoginLocked($username));
    }

    public function testClearLoginAttemptsOnSuccess(): void
    {
        $this->skipIfDbUnavailable();
        $username = 'locktest.success@example.test';
        Auth::clearLoginAttempts($username);
        for ($i = 0; $i < 3; $i++) {
            Auth::logLoginAttempt($username, false);
        }
        $this->assertSame(3, Auth::countLoginFailures($username));
        Auth::clearLoginAttempts($username);
        $this->assertSame(0, Auth::countLoginFailures($username));
    }

    public function testEnforceCsrfTokenSkipsRegisterPost(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['url'] = 'register';
        Auth::enforceCsrfToken();
        $this->assertTrue(true);
    }

    public function testEnforceCsrfTokenNoLongerSkipsForgotPassword(): void
    {
        // ر.ق 2 (بند 9): مسار forgot-password أُزيل كليًا — لم يعد معفًى من CSRF.
        $this->skipIfDbUnavailable();
        $script = $this->tempFile('php');
        file_put_contents($script, '<?php
require ' . var_export(APP_ROOT . '/tests/bootstrap.php', true) . ';
$_SESSION["user_id"] = 1;
$_SERVER["REQUEST_METHOD"] = "POST";
$_GET["url"] = "forgot-password";
\App\Core\Auth::enforceCsrfToken();
echo "SURVIVED";
');
        $result = $this->runCli([$script]);
        $this->assertTrue($result['exit'] === 0, 'ر.ق 2: forgot-password لم يعد ضمن skipRoutes — الرفض يتم عبر exit داخل enforceCsrfToken');
        $this->assertFalse(str_contains($result['stdout'], 'SURVIVED'), 'يجب ألا يصل التنفيذ بعد الرفض (المسار الميت لم يعد معفيًا)');
    }

    public function testEnforceCsrfTokenSkipsApiPost(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['url'] = 'api/v1/health';
        Auth::enforceCsrfToken();
        $this->assertTrue(true);
    }

    // ======================================================================
    // بند 4 — الخروج عبر POST فقط
    // ======================================================================

    public function testLogoutRouteIsPostOnly(): void
    {
        $router = require APP_ROOT . '/routes/web.php';
        $ref = new ReflectionClass($router);
        $prop = $ref->getProperty('routes');
        $prop->setAccessible(true);
        $map = $prop->getValue($router);

        $this->assertFalse(array_key_exists('logout', $map['GET']));
        $this->assertSame('App\Controllers\AuthController@logout', $map['POST']['logout'] ?? null);
    }

    // ======================================================================
    // بند 7 — حارس Admin/UserController يوقف التنفيذ فورًا بعد 403
    // ======================================================================

    public function testAdminUserControllerGuardStopsNonAdmin(): void
    {
        $this->skipIfDbUnavailable();
        $nonAdmin = 'cd004.item7.staff.' . uniqid() . '@test.local';
        $admin    = 'cd004.item7.admin.' . uniqid() . '@test.local';
        $this->seededUsernames = [$nonAdmin, $admin];
        $this->cleanupStatusUsers();

        $staffId  = $this->seedStatusUser($nonAdmin, 'active');
        $adminId  = $this->seedAdminUser($admin);
        // دور Staff (غير Admin) للمستخدم الأول
        $this->testDb()->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, 4)')->execute([$staffId]);

$script = $this->tempFile('php');
        $boot  = var_export(APP_ROOT . '/tests/bootstrap.php', true);
        $staff = var_export($staffId, true);
        $admin = var_export($adminId, true);
        // عملية فرعية (1): مستخدم غير Admin — الحارس يجب أن يعرض 403 ويوقف التنفيذ
        file_put_contents($script, '<?php
require ' . $boot . ';
$_SESSION["user_id"]    = ' . $staff . ';
$_SESSION["user_roles"] = ["Staff"];
echo "PHASE1_START" . PHP_EOL;
$c1 = new \App\Controllers\Admin\UserController();
$c1->index();
echo "PHASE1_SENTINEL" . PHP_EOL;
');
        $res = $this->runCli([$script], $stdoutFile = $this->tempFile('out'));
        $out = is_file($stdoutFile) ? (string)file_get_contents($stdoutFile) : (string)($res['stdout'] ?? '');

        // 1) يجب عرض صفحة 403 عند المحاولة
        $this->assertStringContainsString('403', $out, 'يجب عرض صفحة 403 للمستخدم غير Admin');
        $this->assertStringContainsString('هذه المساحة ليست ضمن صلاحياتك', $out);
        // 2) بند 7: لا يجب أن يستمر التنفيذ إلى دالة index() الإدارية
        $this->assertFalse(str_contains($out, 'PHASE1_SENTINEL'), 'بند 7: التقيد بعدم إكمال دالة الإدارة بعد 403');
        // 3) لا يجب عرض *محتوى* صفحة إدارة المستخدمين (admin/users/index.php).
        //    تحذير: العبارة «إدارة المستخدمين» تظهر أيضًا في الترويسة
        //    (layouts/header.php:425) كرابط قائمة لمن يملك users.view — وهو سلوك
        //    مقصود ومتعمَّد (تعليق البند 7 في header)، ففحصها نصًّا على كامل
        //    المخرَج كان يفشل خطأً ويقيس الترويسة لا الحارس.
        //    العلامة الفريدة الصحيحة: «إضافة مستخدم» — موجودة في
        //    admin/users/index.php:9 وغير موجودة إطلاقًا في header (قياس: 0).
        $this->assertFalse(
            str_contains($out, 'إضافة مستخدم'),
            'لا يجب الوصول لصفحة إدارة المستخدمين'
        );

        // عملية فرعية (2): مستخدم Admin — الحارس يجب أن يسمح بالمرور عبر الكونستركتور
        $script2 = $this->tempFile('php');
        file_put_contents($script2, '<?php
require ' . $boot . ';
$_SESSION["user_id"]    = ' . $admin . ';
$_SESSION["user_roles"] = ["Admin"];
new \App\Controllers\Admin\UserController();
echo "ADMIN_PASSED" . PHP_EOL;
');
        $res2 = $this->runCli([$script2], $stdoutFile2 = $this->tempFile('out'));
        $out2 = is_file($stdoutFile2) ? (string)file_get_contents($stdoutFile2) : (string)($res2['stdout'] ?? '');
        // 4) تحقق إيجابي: المستخدم Admin يمر عبر الحارس
        $this->assertStringContainsString('ADMIN_PASSED', $out2, 'الحارس يجب أن يسمح للمستخدم Admin');
    }

    // ======================================================================
    // بند 14 — دور Admin ديناميكي (بدون معرّف صريح لدور Admin في منطق التشغيل)
    // ======================================================================

    /** عدّاد إشعارات بعنوان يحتوي needle (قياس مباشر على DB). */
    private function notificationTitleCount(string $needle): int
    {
        $db = $this->testDb();
        $st = $db->prepare('SELECT COUNT(*) FROM notifications WHERE title LIKE ?');
        $st->execute(['%' . $needle . '%']);
        return (int)$st->fetchColumn();
    }

    /**
     * ينقل دور 'Admin' النظامي مؤقتًا إلى معرّف جديد كليًا:
     * يعيد تسمية الدور الحالي ويُدخل دور Admin جديدًا (name+is_system_role=1) بمعرّف مختلف.
     * @return array{oldId:int, temp:string, newId:int}
     */
    private function rotateAdminRole(): array
    {
        $db = $this->testDb();
        $oldId = (int)$db->query("SELECT id FROM roles WHERE name = 'Admin' LIMIT 1")->fetchColumn();
        $temp = 'tmp_cd004_item14_old_' . uniqid();
        $db->prepare('UPDATE roles SET name = ?, is_system_role = 0 WHERE id = ?')->execute([$temp, $oldId]);
        $db->prepare("INSERT INTO roles (name, description, is_system_role) VALUES ('Admin', 'دور مؤقت لاختبار البند 14', 1)")->execute();
        $newId = (int)$db->lastInsertId();
        \App\Core\Auth::clearAdminRoleIdsCache();
        return ['oldId' => $oldId, 'temp' => $temp, 'newId' => $newId];
    }

    private function restoreAdminRole(array $ctx): void
    {
        $db = $this->testDb();
        $db->prepare('DELETE FROM user_roles WHERE role_id = ?')->execute([$ctx['newId']]);
        $db->prepare('DELETE FROM roles WHERE id = ?')->execute([$ctx['newId']]);
        $db->prepare("UPDATE roles SET name = 'Admin', is_system_role = 1 WHERE id = ?")->execute([$ctx['oldId']]);
        \App\Core\Auth::clearAdminRoleIdsCache();
    }

    /** يجرّد دور Admin النظامي مؤقتًا من شرط الأهلية (name + is_system_role=1) بلا بديل. */
    private function demoteSystemAdminRole(): array
    {
        $db = $this->testDb();
        $oldId = (int)$db->query("SELECT id FROM roles WHERE name = 'Admin' LIMIT 1")->fetchColumn();
        $temp = 'tmp_cd004_item14_tmp_' . uniqid();
        $db->prepare('UPDATE roles SET name = ?, is_system_role = 0 WHERE id = ?')->execute([$temp, $oldId]);
        \App\Core\Auth::clearAdminRoleIdsCache();
        return ['oldId' => $oldId, 'temp' => $temp];
    }

    private function restoreSystemAdminRole(array $ctx): void
    {
        $this->testDb()->prepare("UPDATE roles SET name = 'Admin', is_system_role = 1 WHERE id = ?")->execute([$ctx['oldId']]);
        \App\Core\Auth::clearAdminRoleIdsCache();
    }

    public function testNotifyAdminsReachesDynamicAdminRoleId(): void
    {
        $this->skipIfDbUnavailable();
        $admin = 'cd004.item14.dyn.' . uniqid() . '@test.local';
        $this->seededUsernames = [$admin];
        $this->cleanupStatusUsers();

        $adminId = $this->seedAdminUser($admin);

        $ctx = $this->rotateAdminRole();
        try {
            // أعد ربط مستخدم الاختبار بالدور الجديد (معرّف != 1 حتمًا)
            $this->testDb()->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$adminId]);
            $this->testDb()->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$adminId, $ctx['newId']]);
            \App\Core\Auth::clearAdminRoleIdsCache();

            $needle = 'CD004_ITEM14_DYN_' . uniqid();
            \App\Core\Auth::notifyAdmins($needle, 'رسالة اختبار البند 14');

            // الدور الجديد بمعرّف != 1 استقبله الإشعار -> البحث ديناميكي وليس 1
            $this->assertSame(1, $this->notificationTitleCount($needle), 'الإشعار يجب أن يصل عبر دور Admin بمعرّف مختلف عن 1');
        } finally {
            $this->testDb()->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$adminId]);
            $this->restoreAdminRole($ctx);
            $this->testDb()->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$adminId, $ctx['oldId']]);
        }
    }

    public function testNotifyAdminsFallsBackWhenNoSystemAdminRole(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.item14.fb.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u];
        $this->cleanupStatusUsers();
        $this->seedStatusUser($u, 'active');

        $ctx = $this->demoteSystemAdminRole();
        try {
            $needle = 'CD004_ITEM14_FALLBACK_' . uniqid();
            \App\Core\Auth::notifyAdmins($needle, 'يجب ألا يُرسل شيء');

            // Fallback آمن: بلا دور Admin نظامي -> لا إشعار
            $this->assertSame(0, $this->notificationTitleCount($needle), 'لا إشعار عند غياب دور Admin النظامي');
            // ويُسجَّل خطأ في سجل PHP
            $log = is_file(APP_ROOT . '/storage/logs/php-error.log')
                ? (string)file_get_contents(APP_ROOT . '/storage/logs/php-error.log')
                : '';
            $this->assertStringContainsString('CD004_ITEM14', $log, 'يجب تسجيل وسم الخطأ في storage/logs/php-error.log');
        } finally {
            $this->restoreSystemAdminRole($ctx);
        }
    }

    // ======================================================================
    // بند 1 — فحص users.status بعد password_verify
    // ======================================================================

    public function testLoginRejectsSuspendedAccount(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.suspended.' . uniqid() . '@test.local';
        $admin = 'cd004.admin.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u, $admin];
        $this->cleanupStatusUsers();

        $userId = $this->seedStatusUser($u, 'suspended');
        $adminId = $this->seedAdminUser($admin);

        $res = $this->runCli([$this->loginScript($u)]);

        $this->assertStringContainsString('SESSION_USER_ID=none', $res['stdout']);
        $this->assertStringContainsString('تم تعليق حسابك', $res['stdout']);
        $this->assertFalse(str_contains($res['stdout'], 'SURVIVED_LOGIN'), 'الرفض يجب أن يقصر التنفيذ إلى /login');
        $this->assertTrue($this->auditExists($userId, 'USER_LOGIN_SUSPENDED'));
        $this->assertTrue($this->notificationForUser($adminId, $u));
    }

    public function testLoginRejectsInvitedAccount(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.invited.' . uniqid() . '@test.local';
        $admin = 'cd004.admin.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u, $admin];
        $this->cleanupStatusUsers();

        $userId = $this->seedStatusUser($u, 'invited');
        $adminId = $this->seedAdminUser($admin);

        $res = $this->runCli([$this->loginScript($u)]);

        $this->assertStringContainsString('SESSION_USER_ID=none', $res['stdout']);
        $this->assertStringContainsString('لم يتم تفعيل حسابك', $res['stdout']);
        $this->assertFalse(str_contains($res['stdout'], 'SURVIVED_LOGIN'), 'الرفض يجب أن يقصر التنفيذ إلى /login');
        $this->assertTrue($this->auditExists($userId, 'USER_LOGIN_INVITED'));
        $this->assertTrue($this->notificationForUser($adminId, $u));
    }

    public function testLoginContinuesForActiveAccount(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.active.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u];
        $this->cleanupStatusUsers();

        $userId = $this->seedStatusUser($u, 'active');

        $res = $this->runCli([$this->loginScript($u)]);

        $this->assertStringContainsString('SESSION_USER_ID=' . (string)$userId, $res['stdout']);
        $this->assertStringContainsString('FLASH=[]', $res['stdout']);
        $this->assertFalse(str_contains($res['stdout'], 'SURVIVED_LOGIN'), 'النجاح يجب أن يوجّه إلى /dashboard');
        $this->assertTrue($this->auditExists($userId, 'USER_LOGIN_SUCCESS'));
    }

    // ======================================================================
    // بند 11 — سياسة كلمة المرور
    // المعتمدة: طول 8 فأكثر + تنوع 3/4 + رفض آخر 3 (نبذ التاريخ) + قائمة شائعة + بلا انتهاء صلاحية تلقائي
    // ======================================================================

    /** يبذر مستخدمًا نشطًا بكلمة مرور أولية معروفة (رمز الهاش مباشرة، بلا تسجيل تاريخ). */
    private function seedPolicyUser(string $username, string $initialPassword): int
    {
        $db = $this->testDb();
        $db->prepare('INSERT INTO users (username, email, password_hash, status) VALUES (?, ?, ?, ?)')
            ->execute([$username, $username, password_hash($initialPassword, PASSWORD_DEFAULT), 'active']);
        // البند 11-b: تسجيل الاسم حتى يمسحه tearDown (وإلا تسرّب).
        $this->seededUsernames[] = $username;
        return (int)$db->lastInsertId();
    }

    /** عدد صفوف password_history لمستخدم (يُجرف تلقائيًا عند حذف المستخدم — ON DELETE CASCADE). */
    private function historyCount(int $userId): int
    {
        $db = $this->testDb();
        $st = $db->prepare('SELECT COUNT(*) FROM password_history WHERE user_id = ?');
        $st->execute([$userId]);
        return (int)$st->fetchColumn();
    }

    public function testPasswordPolicyRulesReflectApprovedPolicy(): void
    {
        $rules = Auth::passwordPolicyRules();
        $this->assertArrayHasKey('min_length', $rules);
        $this->assertArrayHasKey('variety', $rules);
        $this->assertArrayHasKey('history', $rules);
        $this->assertArrayHasKey('common', $rules);
        $this->assertArrayHasKey('expiry', $rules);
        $this->assertStringContainsString('8 أحرف', $rules['min_length']);
        $this->assertStringContainsString('3 من 4', $rules['variety']);
        $this->assertStringContainsString('لا انتهاء صلاحية تلقائي', $rules['expiry']);
        // التزام بالقرار المعتمد: قيم الثوابت نفسها
        $this->assertSame(8, Auth::PASSWORD_POLICY_MIN_LENGTH);
        $this->assertSame(3, Auth::PASSWORD_POLICY_HISTORY);
    }

    public function testValidatePasswordPolicyAcceptsCompliantPassword(): void
    {
        $r = Auth::validatePasswordPolicy('Aa1!Passphrase9');
        $this->assertTrue($r['ok']);
        $this->assertSame([], $r['errors']);
    }

    public function testValidatePasswordPolicyRejectsEmptyPassword(): void
    {
        $r = Auth::validatePasswordPolicy('');
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('مطلوبة', $r['errors'][0]);
    }

    public function testValidatePasswordPolicyRejectsShortPassword(): void
    {
        $r = Auth::validatePasswordPolicy('Aa!9x');
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('8 أحرف على الأقل', $r['errors'][0]);
    }

    public function testValidatePasswordPolicyRejectsVarietyUnderThreeOfFour(): void
    {
        // حروف صغيرة + أرقام فقط = فئتان من 4 -> مرفوضة
        $r = Auth::validatePasswordPolicy('abcdefgh123456');
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('3 من 4 أنواع', $r['errors'][0]);
        // أحرف كبيرة + رموز فقط = فئتان من 4 -> مرفوضة (الطول مطابق والسوداء غير منطبقة)
        $r2 = Auth::validatePasswordPolicy('ABCDEFGH!!!!');
        $this->assertFalse($r2['ok']);
        $this->assertStringContainsString('3 من 4 أنواع', $r2['errors'][0]);
    }

    public function testValidatePasswordPolicyRejectsCommonBlacklistPassword(): void
    {
        // «Password123!» يستوفي الطول والتنوع لكنه في القائمة الشائعة -> يُرفض للسبب نفسه فقط
        $r = Auth::validatePasswordPolicy('Password123!');
        $this->assertFalse($r['ok']);
        $this->assertCount(1, $r['errors']);
        $this->assertStringContainsString('القائمة الشائعة', $r['errors'][0]);
        $this->assertFalse(Auth::validatePasswordPolicy('Welcome1!')['ok']);
        $this->assertFalse(Auth::validatePasswordPolicy('Admin123!')['ok']);
    }

    public function testValidatePasswordPolicyRejectsReuseOfLastThreeAndAllowsPrunedFourth(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.item11.hist.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u];
        $this->cleanupStatusUsers();

        $userId = $this->seedPolicyUser($u, 'Aa0!Pass0');
        $userModel = new \App\Models\User();

        // ثلاث تغييرات ناجحة -> التاريخ يحتوي [Aa0, Aa1, Aa2]
        $this->assertTrue($userModel->updatePassword($userId, 'Aa1!Pass1'));
        $this->assertTrue($userModel->updatePassword($userId, 'Aa2!Pass2'));
        $this->assertTrue($userModel->updatePassword($userId, 'Aa3!Pass3'));
        $this->assertSame(3, $this->historyCount($userId), 'يُحتفظ بآخر 3 كلمات فقط');

        // رفض آخر 3 كلمات (Aa2 الآنية على التوالي بدلًا من السابقة، + السابقتين)
        $this->assertFalse($userModel->updatePassword($userId, 'Aa2!Pass2'), 'رابعاً: كلمة ضمن آخر 3 مرفوضة');
        $this->assertNotEmpty($userModel->lastPasswordErrors, 'يجب تعبئة أخطاء السياسة عند الرفض');
        $this->assertFalse($userModel->updatePassword($userId, 'Aa1!Pass1'), 'كلمة سابقة (2) مرفوضة');
        $this->assertFalse($userModel->updatePassword($userId, 'Aa0!Pass0'), 'كلمة أقدم ضمن آخر 3 مرفوضة');

        // التغيير الرابع يُزيح Aa0 من التاريخ (يبقى [Aa1, Aa2, Aa3])
        $this->assertTrue($userModel->updatePassword($userId, 'Aa4!Pass4'));
        $this->assertSame(3, $this->historyCount($userId), 'الاستبقاء ثابت عند 3 بعد التغيير الرابع');

        // Aa0 خرجت من آخر 3 -> مسموحة الآن
        $this->assertTrue($userModel->updatePassword($userId, 'Aa0!Pass0'), 'كلمة مُزيحة عن آخر 3 أصبحت مسموحة');
        $this->assertSame([], $userModel->lastPasswordErrors, 'تفرّغ أخطاء السياسة عند النجاح');
        // Aa2 ما زالت ضمن آخر 3 -> مرفوضة (محاولة رجعية)
        $this->assertFalse($userModel->updatePassword($userId, 'Aa2!Pass2'));
    }

    public function testUserModelUpdateUserEnforcesPolicy(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.item11.mdl.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u];
        $this->cleanupStatusUsers();

        $userId = $this->seedPolicyUser($u, 'Good@Pass1!');
        $userModel = new \App\Models\User();

        // كلمة مرور مخالفة -> رفض دون حفظ + أخطاء موحّدة
        $rejected = $userModel->updateUser([
            'id' => $userId,
            'username' => $u,
            'email' => $u,
            'employee_id' => null,
            'password' => 'tiny1!',
        ]);
        $this->assertFalse($rejected);
        $this->assertNotEmpty($userModel->lastPasswordErrors);

        // كلمة مرور مطابقة -> نجاح + تسجيل القديم في التاريخ
        $updated = $userModel->updateUser([
            'id' => $userId,
            'username' => $u,
            'email' => $u,
            'employee_id' => null,
            'password' => 'Fine@Pass123!',
        ]);
        $this->assertTrue($updated);
        $this->assertSame(1, $this->historyCount($userId), 'تسجيل الهاش القديم في password_history بعد التغيير');
    }

    public function testGenerateStrongPasswordMeetsPolicy(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $pw = Auth::generateStrongPassword(12);
            $this->assertGreaterThan(11, strlen($pw), 'الطول الافتراضي 12 فأكثر');
            $r = Auth::validatePasswordPolicy($pw);
            $this->assertTrue($r['ok'], 'كلمة المولّد يجب أن تطابق السياسة: ' . $pw . ' ← ' . implode(' | ', $r['errors']));
        }
    }

    // ======================================================================
    // بند 9 — إعادة تعيين كلمة المرور عبر Admin + إلزام التغيير (must_change_password)
    // ======================================================================

    /** يبذر مستخدمًا نشطًا مفعّلًا عليه must_change_password=1. */
    private function seedMustChangeUser(string $username): int
    {
        $db = $this->testDb();
        $db->prepare('INSERT INTO users (username, email, password_hash, status, must_change_password) VALUES (?, ?, ?, ?, 1)')
            ->execute([$username, $username, password_hash(self::TEST_PASSWORD, PASSWORD_DEFAULT), 'active']);
        // البند 11-b: تسجيل الاسم حتى يمسحه tearDown (وإلا تسرّب).
        $this->seededUsernames[] = $username;
        return (int)$db->lastInsertId();
    }

    // ======================================================================
    // بند 10 — شاشة تغيير كلمة المرور (البذرة الإجبارية من بند 9 + التغيير الحُر)
    // ======================================================================

    /** مساعد سكربت OC: يطلق changePassword كمتصل حقيقي (جلسة موثقة أو زائر) ويلتقط FLASH/LOCATION. */
    private function changePasswordScript(int $userId, string $current, string $new, string $confirm, bool $guest = false): string
    {
        $script = $this->tempFile('php');
        $boot = var_export(APP_ROOT . '/tests/bootstrap.php', true);
        $cur = var_export($current, true);
        $neu = var_export($new, true);
        $con = var_export($confirm, true);
        file_put_contents($script, '<?php
require ' . $boot . ';
$_SERVER["REQUEST_METHOD"] = "POST";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
$_POST["current_password"] = ' . $cur . ';
$_POST["password"] = ' . $neu . ';
$_POST["password_confirm"] = ' . $con . ';
if (' . ($guest ? 'true' : 'false') . ') { $_SESSION = []; } else { $_SESSION["user_id"] = ' . $userId . '; }
register_shutdown_function(function () {
    echo "FLASH=" . json_encode($_SESSION[\App\Core\Flash::FLASH_KEY] ?? [], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    echo "LOCATION=" . (headers_sent() ? "sent" : implode("|", headers_list())) . PHP_EOL;
});
try {
    (new \App\Controllers\AuthController())->changePassword();
} catch (\Throwable $e) {
    echo "ERROR=" . $e->getMessage() . PHP_EOL;
}
echo "SURVIVED_CHANGE";
');
        return $script;
    }

    /** مساعد سكربت OC: يعرض شاشة تغيير كلمة المرور (GET) ويجمع HTML المطبوع. */
    private function changePasswordPageScript(?int $userId): string
    {
        $script = $this->tempFile('php');
        $boot = var_export(APP_ROOT . '/tests/bootstrap.php', true);
        file_put_contents($script, '<?php
require ' . $boot . ';
$_SERVER["REQUEST_METHOD"] = "GET";
if (' . ($userId === null ? 'true' : 'false') . ') { $_SESSION = []; } else { $_SESSION["user_id"] = ' . (int)$userId . '; $_SESSION["user_object"] = (object)["username" => "probe"]; }
register_shutdown_function(function () {
    echo "LOCATION=" . (headers_sent() ? "sent" : implode("|", headers_list())) . PHP_EOL;
});
try {
    (new \App\Controllers\AuthController())->showChangePassword();
} catch (\Throwable $e) {
    echo "ERROR=" . $e->getMessage() . PHP_EOL;
}
echo "SURVIVED_PAGE";
');
        return $script;
    }

    private function passwordHashOf(int $userId): string
    {
        $st = $this->testDb()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $st->execute([$userId]);
        return (string)$st->fetchColumn();
    }

    private function mustChangeFlagOf(int $userId): int
    {
        $st = $this->testDb()->prepare('SELECT must_change_password FROM users WHERE id = ?');
        $st->execute([$userId]);
        return (int)$st->fetchColumn();
    }

    public function testChangePasswordRequiresLoginRejectsGuest(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.item10.guest.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u];
        $this->cleanupStatusUsers();
        $userId = $this->seedPolicyUser($u, 'Old@Pass11!');

        $res = $this->runCli([$this->changePasswordScript($userId, 'Old@Pass11!', 'New@Pass22!', 'New@Pass22!', true)]);
        $this->assertStringContainsString('يجب تسجيل الدخول أولاً', $res['stdout']);
        // CLI تُرسِّل الترويسات قبل shutdown (LOCATION=sent) — التحقق من وجهة المتحكم ساكنًا كما في بند 9
        $authCtrl = (string)file_get_contents(APP_ROOT . '/app/Controllers/AuthController.php');
        $this->assertStringContainsString("header('Location: ' . APP_URL . '/login');", $authCtrl, 'وجهة الزائر: شاشة الدخول');
        // لم يحدث أي تغيير على هاش الزائر الغير موثق
        $this->assertTrue(password_verify('Old@Pass11!', $this->passwordHashOf($userId)));
    }

    public function testChangePasswordPageShownForAuthenticatedUser(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.item10.page.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u];
        $this->cleanupStatusUsers();
        $userId = $this->seedPolicyUser($u, 'Old@Pass11!');

        // OOS #8 في هذا البيئة: خرج كبير عبر أنبوب Windows (~>4KB) يشلّ runCli — نصيّب stdout إلى ملف
        $outFile = $this->tempFile('html');
        $this->runCli([$this->changePasswordPageScript($userId)], $outFile);
        $html = (string)file_get_contents($outFile);
        $this->assertStringContainsString('/change-password"', $html);
        $this->assertStringContainsString('name="current_password"', $html);
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('name="_token"', $html, 'توكن السلة مطلوب للـ POST (بند 4)');
        $this->assertStringContainsString('تغيير كلمة المرور', $html);
        $this->assertStringContainsString('SURVIVED_PAGE', $html);
    }

    public function testChangePasswordPageRedirectsGuestToLogin(): void
    {
        $this->skipIfDbUnavailable();
        $res = $this->runCli([$this->changePasswordPageScript(null)]);
        // الزائر يخرج عند requireLogin (لا يصل إلى نهاية السكربت) — نطمئن من حدث التوجيه ساكنًا
        $authCtrl = (string)file_get_contents(APP_ROOT . '/app/Controllers/AuthController.php');
        $this->assertStringContainsString("header('Location: ' . APP_URL . '/login');", $authCtrl, 'وجهة الزائر: شاشة الدخول');
    }

    public function testChangePasswordRejectsWrongCurrentPassword(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.item10.wrongcur.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u];
        $this->cleanupStatusUsers();
        $userId = $this->seedPolicyUser($u, 'Old@Pass11!');

        $res = $this->runCli([$this->changePasswordScript($userId, 'Wrong@Pass!', 'New@Pass22!', 'New@Pass22!')]);
        $this->assertStringContainsString('كلمة المرور الحالية غير صحيحة', $res['stdout']);
        $this->assertTrue(password_verify('Old@Pass11!', $this->passwordHashOf($userId)), 'الهاش يجب ألا يتغير');
    }

    public function testChangePasswordRejectsConfirmMismatch(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.item10.mismatch.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u];
        $this->cleanupStatusUsers();
        $userId = $this->seedPolicyUser($u, 'Old@Pass11!');

        $res = $this->runCli([$this->changePasswordScript($userId, 'Old@Pass11!', 'New@Pass22!', 'New@Pass23!')]);
        $this->assertStringContainsString('غير مطابق', $res['stdout']);
        $this->assertTrue(password_verify('Old@Pass11!', $this->passwordHashOf($userId)));
    }

    public function testChangePasswordEnforcesPolicyViaScreen(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.item10.policy.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u];
        $this->cleanupStatusUsers();
        $userId = $this->seedPolicyUser($u, 'Old@Pass11!');

        $res = $this->runCli([$this->changePasswordScript($userId, 'Old@Pass11!', 'tiny1!', 'tiny1!')]);
        $this->assertStringContainsString('8 أحرف على الأقل', $res['stdout']);
        $this->assertTrue(password_verify('Old@Pass11!', $this->passwordHashOf($userId)), 'رفض سياسية بلا حفظ');
    }

    public function testChangePasswordRejectsReuseFromHistory(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.item10.hist.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u];
        $this->cleanupStatusUsers();
        $userId = $this->seedPolicyUser($u, 'Aa0!Pass0');
        $userModel = new \App\Models\User();
        // التاريخ: [Aa0, Aa1] ثم الحالية Aa2
        $this->assertTrue($userModel->updatePassword($userId, 'Aa1!Pass1'));
        $this->assertTrue($userModel->updatePassword($userId, 'Aa2!Pass2'));

        $res = $this->runCli([$this->changePasswordScript($userId, 'Aa2!Pass2', 'Aa1!Pass1', 'Aa1!Pass1')]);
        $this->assertStringContainsString('آخر 3 كلمات', $res['stdout'], 'الشاشة ترفض إعادة استخدام كلمة داخل النبذ التاريخي');
        $this->assertTrue(password_verify('Aa2!Pass2', $this->passwordHashOf($userId)));
    }

    public function testChangePasswordSuccessClearsMustChangeFlagRedirectsDashboardAndAudits(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.item10.success.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u];
        $this->cleanupStatusUsers();
        $userId = $this->seedMustChangeUser($u);
        $this->assertSame(1, $this->mustChangeFlagOf($userId), 'الشرط مفعّل مسبقًا (بند 9)');

        $res = $this->runCli([$this->changePasswordScript($userId, self::TEST_PASSWORD, 'New@Pass99!', 'New@Pass99!')]);
        $this->assertStringContainsString('تم تغيير كلمة المرور بنجاح', $res['stdout']);
        $this->assertStringContainsString('LOCATION=sent', $res['stdout'], 'المعالج يصدر التوجيه ثم يخرج (ترويسات CLI مُرسلة مسبقًا)');
        $authCtrl = (string)file_get_contents(APP_ROOT . '/app/Controllers/AuthController.php');
        $this->assertStringContainsString("APP_URL . '/dashboard'", $authCtrl, 'وجهة النجاح: لوحة التحكم');
        $this->assertSame(0, $this->mustChangeFlagOf($userId), 'نجاح التغيير يمسح إلزام البند 9');
        $this->assertTrue(password_verify('New@Pass99!', $this->passwordHashOf($userId)));
        $this->assertSame(1, $this->historyCount($userId), 'تسجيل هاش ما قبل التغيير في password_history');
        $this->assertTrue($this->auditExists($userId, 'USER_PASSWORD_CHANGE'));
    }

    public function testUpdatePasswordWithMustChangeFlagSetsFlag(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.item9.flag.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u];
        $this->cleanupStatusUsers();

        $userId = $this->seedPolicyUser($u, 'Old@Pass11!');
        $userModel = new \App\Models\User();

        // إعادة التعيين عبر Admin (بند 9): تحديث ملوّن بـ $mustChange=true
        $this->assertTrue($userModel->updatePassword($userId, 'New@Pass22!', true));
        $st = $this->testDb()->prepare('SELECT must_change_password FROM users WHERE id = ?');
        $st->execute([$userId]);
        $this->assertSame(1, (int)$st->fetchColumn(), 'يجب تفعيل must_change_password بعد إعادة التعيين');
        $this->assertSame(1, $this->historyCount($userId), 'تسجيل هاش القديم في password_history');

        // التحديث العادي (بلا المعلَّم) يمسح العلم: كلمة المرور تغيّرت فسقط الشرط
        $this->assertTrue($userModel->updatePassword($userId, 'Third@Pass33!'));
        $st->execute([$userId]);
        $this->assertSame(0, (int)$st->fetchColumn(), 'التحديث العادي يجب أن يمسح must_change_password (سقوط الشرط)');
    }

    public function testLoginForcesMustChangePasswordToChangePassword(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.item9.must.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u];
        $this->cleanupStatusUsers();

        $userId = $this->seedMustChangeUser($u);

        $res = $this->runCli([$this->loginScript($u)]);

        // الدخول نجح (المصادقة صحيحة) لكن وُجِّه إجبارًا: رسالة الالتزام + مسار التغيير (تحقق من التأثير + التحقق الساكن للوجهة)
        $this->assertStringContainsString('SESSION_USER_ID=' . (string)$userId, $res['stdout']);
        $this->assertStringContainsString('يجب تغيير كلمة المرور قبل المتابعة', $res['stdout'], 'فرع الإلزام يجب أن يضبط رسالة الالتزام');
        $this->assertTrue($this->auditExists($userId, 'USER_LOGIN_SUCCESS'));
        // تحقق ساكن: فرع الإلزام يوجّه إلى /change-password (CLI تُرسِّل الترويسات قبل shutdown فلا تُلتقط headers_list)
        $authCtrl = (string)file_get_contents(APP_ROOT . '/app/Controllers/AuthController.php');
        $this->assertStringContainsString("/change-password'", $authCtrl, 'وجهة فرع الإلزام هي شاشة تغيير كلمة المرور');
    }

    public function testForgotPasswordDeadSurfaceRemoved(): void
    {
        // ر.ق 2 (بند 9): لا واجهة/مسار لاستعادة كلمة المرور — يُرفع رابط "#" الميت والملف الفارغ.
        $this->assertFalse(file_exists(APP_ROOT . '/app/Views/auth/forgot-password.php'), 'الملف forgot-password.php الفارغ يجب أن يُحذف');
        $loginView = (string)file_get_contents(APP_ROOT . '/app/Views/auth/login.php');
        $this->assertFalse(str_contains($loginView, 'forgot'), 'صفحة الدخول يجب ألا تحوي رابط/مرجع استعادة كلمة المرور');
        $routesSrc = (string)file_get_contents(APP_ROOT . '/routes/web.php');
        $this->assertFalse(str_contains($routesSrc, 'forgot'), 'لا مسار استعادة كلمة المرور');
        // وإشارة النطاق: إعادة التعيين تتم حصريًا عبر Admin (skipRoutes لم تعد تشمل forgot-password)
        $authSrc = (string)file_get_contents(APP_ROOT . '/app/Core/Auth.php');
        $this->assertStringContainsString("['register', 'reset-password']", $authSrc, 'skipRoutes بلا forgot-password (ر.ق 2)');
    }

    // ======================================================================
    // بند 8 — انعاش نسخة الصلاحيات (permissions_version)
    // العمود users.permissions_version + مشغلات DB + حارس الطلب بنافذة 60 ثانية
    // ======================================================================

    public function testTriggerOnUserRolesAssignAndRemoveBumpsVersion(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.item8.urole.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u];
        $this->cleanupStatusUsers();

        $userId = $this->seedStatusUser($u, 'active');
        $roleId = $this->roleIdByName('Staff') ?? $this->seedRole('cd004_role_staff_' . uniqid());
        $v0 = $this->permissionsVersionOfId($userId);

        $this->testDb()->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$userId, $roleId]);
        $this->assertSame($v0 + 1, $this->permissionsVersionOfId($userId), 'إسناد دور يرفع نسخة المستخدم مرة واحدة (مشغل AFTER INSERT)');

        $this->testDb()->prepare('DELETE FROM user_roles WHERE user_id = ? AND role_id = ?')->execute([$userId, $roleId]);
        $this->assertSame($v0 + 2, $this->permissionsVersionOfId($userId), 'إزالة دور ترفع نسخة المستخدم مرة واحدة (مشغل AFTER DELETE)');
    }

    public function testTriggerOnRolePermissionsBumpsEachUserOfRole(): void
    {
        $this->skipIfDbUnavailable();
        $roleName = 'cd004.role.perm.' . uniqid();
        $roleId = $this->seedRole($roleName);

        $names = [];
        for ($i = 0; $i < 3; $i++) {
            $names[] = 'cd004.item8.multi.' . $i . '.' . uniqid() . '@test.local';
        }
        $this->seededUsernames = $names;
        $this->cleanupStatusUsers();

        $users = [];
        foreach ($names as $un) {
            $uid = $this->seedStatusUser($un, 'active');
            $this->testDb()->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$uid, $roleId]);
            $users[$uid] = $this->permissionsVersionOfId($uid);
        }

        // مستخدم تحكمي بدور مختلف — لا يجب أن يتأثر بتعديل صلاحيات دورنا
        $ctrlName = 'cd004.item8.ctrl.' . uniqid() . '@test.local';
        $this->seededUsernames[] = $ctrlName;
        $ctrlId = $this->seedStatusUser($ctrlName, 'active');
        $staffRoleId = $this->roleIdByName('Staff');
        if ($staffRoleId !== null && $staffRoleId !== $roleId) {
            $this->testDb()->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$ctrlId, $staffRoleId]);
        }
        $ctrlBase = $this->permissionsVersionOfId($ctrlId);

        $permId = (int)$this->testDb()->query('SELECT id FROM permissions ORDER BY id LIMIT 1')->fetchColumn();
        $this->assertGreaterThan(0, $permId, 'يجب وجود صلاحية واحدة على الأقل لإجراء الاختبار');

        $db = $this->testDb();
        $db->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)')->execute([$roleId, $permId]);
        foreach ($users as $uid => $base) {
            $this->assertSame($base + 1, $this->permissionsVersionOfId($uid), "تعديل صلاحيات دور بـ3 مستخدمين يجب أن يرفع نسخة كلٍّ منهم مرة واحدة (مستخدم $uid)");
        }
        $this->assertSame($ctrlBase, $this->permissionsVersionOfId($ctrlId), 'مستخدم دور آخر لا يتأثر بتعديل صلاحيات هذا الدور');

        $db->prepare('DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?')->execute([$roleId, $permId]);
        foreach ($users as $uid => $base) {
            $this->assertSame($base + 2, $this->permissionsVersionOfId($uid), "إزالة صلاحية من دور يجب أن ترفع نسخة كل مستخدم مرة واحدة (مستخدم $uid)");
        }
    }

    public function testLoginStampsPermissionsVersionInSession(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.item8.login.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u];
        $this->cleanupStatusUsers();

        $userId = $this->seedStatusUser($u, 'active');
        $dbVersion = $this->permissionsVersionOfId($userId);

        $res = $this->runCli([$this->loginScript($u)]);
        $this->assertStringContainsString('SESSION_USER_ID=' . (string)$userId, $res['stdout']);
        $this->assertMatches('/PERMS_VERSION=' . $dbVersion . '\s/', $res['stdout'] . "\n", 'الدخول يثبّت نسخة الصلاحيات الحالية من DB في الجلسة');
        $this->assertMatches('/PERMS_CHECK_AT=\d+/', $res['stdout'], 'الدخول يثبّت وقت الفحص للجلسة');
    }

    public function testSessionInvalidatedWhenPermissionsVersionDiffers(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.item8.invl.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u];
        $this->cleanupStatusUsers();

        $userId = $this->seedStatusUser($u, 'active');
        $roleId = $this->roleIdByName('Staff') ?? $this->seedRole('cd004_role_staff_' . uniqid());
        $this->testDb()->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$userId, $roleId]);
        $stale = $this->permissionsVersionOfId($userId);

        // تعديل الدور مجددًا → مشغل DELETE/INSERT يرفع النسخة إلى قيمة أحدث
        $this->testDb()->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$userId]);
        $this->testDb()->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$userId, $roleId]);
        $fresh = $this->permissionsVersionOfId($userId);
        $this->assertGreaterThan($stale, $fresh, 'تعديل الدور يجب أن يرفع النسخة (سليمة بُنية الاختبار)');

        $script = $this->tempFile('php');
        file_put_contents($script, '<?php
require ' . var_export(APP_ROOT . '/tests/bootstrap.php', true) . ';
$_SESSION["user_id"] = ' . $userId . ';
$_SESSION["user_roles"] = ["Staff"];
$_SESSION["user_permissions"] = [];
$_SESSION["permissions_version"] = ' . $stale . ';
$_SESSION["permissions_check_at"] = 0;
register_shutdown_function(function () {
    echo "AFTER_USER_ID=" . ($_SESSION["user_id"] ?? "none") . PHP_EOL;
    echo "AFTER_FLASH=" . json_encode($_SESSION[\App\Core\Flash::FLASH_KEY] ?? [], JSON_UNESCAPED_UNICODE) . PHP_EOL;
});
\App\Core\Auth::invalidateSessionIfPermissionsChanged();
echo "SURVIVED_GUARD";
');
        $res = $this->runCli([$script]);
        $this->assertFalse(str_contains($res['stdout'], 'SURVIVED_GUARD'), 'عند اختلاف النسخة يجب ألا يتجاوز التنفيذ الحارس');
        $this->assertStringContainsString('AFTER_USER_ID=none', $res['stdout'], 'بيانات الجلسة تُطهر — إعادة دخول إلزامية');
        $this->assertStringContainsString('يرجى تسجيل الدخول مجددًا', $res['stdout'], 'رسالة الإبطال تصل إلى صفحة الدخول');
        $authSrc = (string)file_get_contents(APP_ROOT . '/app/Core/Auth.php');
        $this->assertStringContainsString("header('Location: ' . APP_URL . '/login');", $authSrc, 'وجهة الإبطال: شاشة الدخول');
    }

    public function testNoInvalidationInsideCacheWindow(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.item8.cache.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u];
        $this->cleanupStatusUsers();

        $userId = $this->seedStatusUser($u, 'active');
        $dbVersion = $this->permissionsVersionOfId($userId);

        // نسخة هرمة عمدًا لكن وقت الفحص حديث (داخل نافذة 60 ثانية)
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_roles'] = ['User'];
        $_SESSION['permissions_version'] = $dbVersion - 1;
        $_SESSION['permissions_check_at'] = time();

        $this->assertFalse(Auth::permissionsNeedRefresh(), 'داخل نافذة 60 ثانية لا تُقرأ نسخة DB ولا يُفشَل الفحص');
        $this->assertTrue(Auth::check(), 'الجلسة تظل سليمة داخل النافذة');

        // فرض انتهاء النافذة → الاختيار عن الهرمة
        $_SESSION['permissions_check_at'] = 0;
        $this->assertTrue(Auth::permissionsNeedRefresh(), 'عند خروج النافذة يُكشف اختلاف النسخة الهرمة');
    }

    public function testLegacySessionWithoutPermissionsVersionInvalidates(): void
    {
        $this->skipIfDbUnavailable();
        $u = 'cd004.item8.legacy.' . uniqid() . '@test.local';
        $this->seededUsernames = [$u];
        $this->cleanupStatusUsers();

        $userId = $this->seedStatusUser($u, 'active');

        $script = $this->tempFile('php');
        file_put_contents($script, '<?php
require ' . var_export(APP_ROOT . '/tests/bootstrap.php', true) . ';
// جلسة قبل بند 8: بلا مفتاحي النسخة إطلاقًا
$_SESSION["user_id"] = ' . $userId . ';
$_SESSION["user_roles"] = ["User"];
$_SESSION["user_permissions"] = [];
register_shutdown_function(function () {
    echo "AFTER_USER_ID=" . ($_SESSION["user_id"] ?? "none") . PHP_EOL;
});
\App\Core\Auth::invalidateSessionIfPermissionsChanged();
echo "SURVIVED_LEGACY";
');
        $res = $this->runCli([$script]);
        $this->assertFalse(str_contains($res['stdout'], 'SURVIVED_LEGACY'), 'جلسة ما قبل بند 8 (بلا نسخة) تُبطل عند أول فحص');
        $this->assertStringContainsString('AFTER_USER_ID=none', $res['stdout']);
    }

    public function testAdminDemotedToStaffInvalidatesSessionAndReloginIsNotAdmin(): void
    {
        $this->skipIfDbUnavailable();
        $adminUser = 'cd004.item8.demote.' . uniqid() . '@test.local';
        $this->seededUsernames = [$adminUser];
        $this->cleanupStatusUsers();

        $adminId = $this->seedAdminUser($adminUser);
        $staffRoleId = $this->roleIdByName('Staff');
        $this->assertNotNull($staffRoleId, 'يجب وجود دور Staff جاهز في DB (is_system_role)');

        $fresh = $this->permissionsVersionOfId($adminId);
        $script = $this->tempFile('php');
        file_put_contents($script, '<?php
require ' . var_export(APP_ROOT . '/tests/bootstrap.php', true) . ';
$_SESSION["user_id"] = ' . $adminId . ';
$_SESSION["user_roles"] = ["Admin"];
$_SESSION["user_permissions"] = ["*"];
$_SESSION["permissions_version"] = ' . $fresh . ';
$_SESSION["permissions_check_at"] = 0;
register_shutdown_function(function () {
    echo "AFTER_USER_ID=" . ($_SESSION["user_id"] ?? "none") . PHP_EOL;
    echo "AFTER_FLASH=" . json_encode($_SESSION[\App\Core\Flash::FLASH_KEY] ?? [], JSON_UNESCAPED_UNICODE) . PHP_EOL;
});
// تغيير الدور قبل الفحص: Admin → Staff
$db = new \PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
$db->prepare("DELETE FROM user_roles WHERE user_id = ?")->execute([' . $adminId . ']);
$db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")->execute([' . $adminId . ', ' . $staffRoleId . ']);
\App\Core\Auth::invalidateSessionIfPermissionsChanged();
echo "SURVIVED_DEMOTE";
');
        $res = $this->runCli([$script]);
        $this->assertFalse(str_contains($res['stdout'], 'SURVIVED_DEMOTE'), 'خفض Admin→Staff يجب أن يبطل جلسة Admin فورًا');
        $this->assertStringContainsString('AFTER_USER_ID=none', $res['stdout'], 'جلسة Admin تُبطل');
        $this->assertStringContainsString('يرجى تسجيل الدخول مجددًا', $res['stdout']);

        // إعادة الدخول بعد التغيير: لم يعد Admin
        $relogin = $this->runCli([$this->loginScript($adminUser)]);
        $this->assertStringContainsString('SESSION_USER_ID=' . (string)$adminId, $relogin['stdout'], 'إعادة الدخول تنجح');
        $this->assertStringContainsString('IS_ADMIN=0', $relogin['stdout'], 'بعد إعادة الدخول لم يعد الموظف مدير نظام (رفض تمرير isAdmin)');
        $this->assertStringContainsString('"Staff"', $relogin['stdout'], 'الأدوار المعاد بناؤها من DB = Staff');
        $this->assertFalse(str_contains($relogin['stdout'], '"Admin"'), 'دور Admin زال من الجلسة بعد التغيير');
    }

    /**
     * البند 11-b: لا يجوز أن يترك تنظيف الاختبارات أي يتيم في user_roles.
     *
     * يثبت أن الاعتماد على CASCADE الحقيقي (بعد حذف SET FOREIGN_KEY_CHECKS = 0)
     * ينظّف فِرَق الأدوار المرتبطة بالحسابات المؤقتة، وأن أي مرجع غير متوقَّع
     * صار يُظهره الفشل بدل أن يُنتج تلفًا صامتًا.
     */
    public function testNoOrphanUserRolesAfterCleanup(): void
    {
        if (!db_available()) {
            $this->markSkipped('DB غير متاحة');
        }

        $db = $this->testDb();
        $orphanCount = static function () use ($db): int {
            return (int)$db->query(
                'SELECT COUNT(*) FROM user_roles ur
                 LEFT JOIN users u ON u.id = ur.user_id
                 WHERE u.id IS NULL'
            )->fetchColumn();
        };

        $before = $orphanCount();

        // بذر مستخدم بدور ثم تنظيفه صراحةً (tearDown سيكرّر التنظيف بأمان)
        $username = 'orphancheck.' . bin2hex(random_bytes(4)) . '@test.local';
        $adminId  = $this->seedAdminUser($username);
        $linked = (int)$db->query('SELECT COUNT(*) FROM user_roles WHERE user_id = ' . $adminId)->fetchColumn();
        $this->assertSame(1, $linked, 'البذر يربط المستخدم المؤقت بدور');

        $this->cleanupStatusUsers();

        $this->assertSame(
            0,
            (int)$db->query('SELECT COUNT(*) FROM users WHERE username = ' . $db->quote($username))->fetchColumn(),
            'المستخدم المؤقت حُذف بالكامل'
        );
        $this->assertSame(
            0,
            (int)$db->query('SELECT COUNT(*) FROM user_roles WHERE user_id = ' . $adminId)->fetchColumn(),
            'فِرَق الأدوار حُذفت مع المستخدم'
        );
        $this->assertSame(
            $before,
            $orphanCount(),
            'التنظيف لم يترك أي يتيم جديد في user_roles'
        );
    }
}
