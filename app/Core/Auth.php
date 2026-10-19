<?php
namespace App\Core;

use App\Models\User;
use App\Controllers\ErrorController;

/**
 * فئة المصادقة والصلاحيات (Authentication & Authorization)
 *
 * توفر مجموعة من الدوال الثابتة (static) للتعامل مع كل ما يخص المستخدم الحالي.
 */
class Auth {

    /**
     * يتحقق مما إذا كان هناك مستخدم مسجل دخوله حالياً.
     * @return bool
     */
    public static function check() {
        return isset($_SESSION['user_id']);
    }

    /**
     * يجلب معرف (ID) المستخدم المسجل دخوله.
     * @return int|null
     */
    public static function id() {
        return $_SESSION['user_id'] ?? null;
    }
 /**
     * يتحقق مما إذا كان المستخدم الحالي يملك دوراً معيناً.
     * هذه الدالة ضرورية لـ UserController.
     * @param string $roleName - اسم الدور (مثال: 'Admin').
     * @return bool
     */
    public static function is($roleName) {
        if (!self::check() || !isset($_SESSION['user_roles'])) {
            return false;
        }
        return in_array($roleName, $_SESSION['user_roles']);
    }
    /**
     * يجلب كامل بيانات المستخدم المسجل دخوله حالياً (من الكاش أو قاعدة البيانات).
     * @return object|false
     */
    public static function user() {
        if (!self::check()) return false;
        return $_SESSION['user_object'] ?? false;
    }

    /**
     * يقوم بتسجيل خروج المستخدم.
     */
  public static function logout() {
        session_unset();
    }


    /**
     * ===> الدالة الجديدة التي كانت مفقودة <===
     * يتحقق مما إذا كان المستخدم الحالي يملك دوراً معيناً.
     * @param string $roleName - اسم الدور (مثال: 'Admin', 'Manager').
     * @return bool
     */
    public static function hasRole($roleName) {
        if (!self::check() || !isset($_SESSION['user_roles'])) {
            return false;
        }
        return in_array($roleName, $_SESSION['user_roles']);
    }
    /**
     * اختصار للتحقق مما إذا كان المستخدم هو مدير نظام.
     * @return bool
     */
    public static function isAdmin() {
        // الآن هذه الدالة ستعمل بشكل صحيح لأنها تعتمد على hasRole
        return self::hasRole('Admin');
    }

    /**
     * يتحقق مما إذا كان المستخدم يملك صلاحية معينة.
     * @param string $permissionName - اسم الصلاحية (مثال: 'employees.view').
     * @return bool
     */
      public static function userHasPermission($permissionName) {
        if (!self::check()) {
            return false;
        }
        if (self::isAdmin()) {
            return true;
        }
        return isset($_SESSION['user_permissions']) && in_array($permissionName, $_SESSION['user_permissions']);
    }


    /**
     * Middleware: يتحقق من الصلاحية، وإذا لم تكن موجودة يعرض صفحة 403.
     * @param string $permissionName
     * @return bool
     */
    public static function can($permissionName) {
        if (!self::userHasPermission($permissionName)) {
            $errorController = new ErrorController();
            $errorController->forbidden($permissionName);
            exit();
        }
        return true;
    }

    /**
     * CD-009: فحص صلاحية منطقي خالص (بلا طباعة 403 وبلا exit).
     * تُستخدم كـbool صريح داخل المنطق (conditions / templates).
     * @param string $permissionName
     * @return bool
     */
    public static function canBool($permissionName) {
        return self::userHasPermission($permissionName);
    }

    /**
     * CD-009: حارس صلاحية صريح — يطبع 403 ويُنهي التنفيذ عند غياب الصلاحية.
     * بديل واضح لاستخدام can() كحارس. لا يُعيد قيمة ذات معنى عند المرور.
     * @param string $permissionName
     * @return void
     */
    public static function enforce($permissionName) {
        if (!self::userHasPermission($permissionName)) {
            $errorController = new ErrorController();
            $errorController->forbidden($permissionName);
            exit();
        }
    }

    // ======================================================================
    // دوال CSRF (حماية من هجمات التزوير)
    // ======================================================================

    /**
     * توليد توكن CSRF جديد وتخزينه في الجلسة
     * @return string التوكن المُنشأ
     */
    public static function generateCsrfToken(): string {
        // إعادة استخدام التوكن الحالي طالما لم تنتهِ صلاحيته (يمنع إبطال توكنات الصفحات الأخرى)
        if (!empty($_SESSION['csrf_token'])
            && !empty($_SESSION['csrf_token_expires'])
            && strtotime($_SESSION['csrf_token_expires']) > time()) {
            return $_SESSION['csrf_token'];
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 7200);

        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_expires'] = $expiresAt;

        // تخزين في قاعدة البيانات فقط للمستخدمين المسجلين (الجدول يتطلب user_id)
        if (self::check()) {
            try {
                $db = \App\Core\Database::getInstance();
                $db->query("INSERT INTO csrf_tokens (user_id, token, expires_at) VALUES (:uid, :token, :exp)");
                $db->bind(':uid', self::id());
                $db->bind(':token', $token);
                $db->bind(':exp', $expiresAt);
                $db->execute();
            } catch (\Exception $e) {
                // تجاهل - الجدول قد لا يكون موجوداً بعد (فترة السماح)
            }
        }

        return $token;
    }

    /**
     * التحقق من توكن CSRF
     * @param string|null $token التوكن المراد التحقق منه
     * @return bool
     */
    public static function validateCsrfToken(?string $token): bool {
        if (empty($token)) {
            return false;
        }

        // التحقق من الجلسة أولاً (تعمل قبل المصادقة للتوكنات الخاصة بصفحة الدخول)
        $valid = isset($_SESSION['csrf_token'])
              && hash_equals($_SESSION['csrf_token'], $token)
              && strtotime($_SESSION['csrf_token_expires'] ?? 'now') > time();

        if (!$valid && self::check()) {
            // التحقق من قاعدة البيانات (للطلبات اللاحقة بعد المصادقة)
            try {
                $db = \App\Core\Database::getInstance();
                $db->query("SELECT id FROM csrf_tokens WHERE token = :token AND user_id = :uid AND used = 0 AND expires_at > NOW() LIMIT 1");
                $db->bind(':token', $token);
                $db->bind(':uid', self::id());
                $result = $db->fetch();
                if ($result) {
                    $valid = true;
                    // تعليم التوكن كمستخدم
                    $db->query("UPDATE csrf_tokens SET used = 1 WHERE id = :id");
                    $db->bind(':id', $result->id);
                    $db->execute();
                }
            } catch (\Exception $e) {
                // الجدول غير موجود - استمر باستخدام جلسة فقط
            }
        }

        if (!$valid) {
            // تسجيل استخدام فترة السماح
            CsrfGraceLogger::logGraceUsage('فشل التحقق من CSRF', 'الطلب: ' . ($_SERVER['REQUEST_URI'] ?? 'غير معروف'));
        }

        // التحقق من فترة السماح
        CsrfGraceLogger::checkAndAutoClose();
        if (!$valid && CsrfGraceLogger::isGraceActive()) {
            return true;
        }

        return $valid;
    }

    /**
     * إجبار وجود توكن CSRF صحيح (للمسارات POST)
     * يتم تخطي الفحص لمسارات ما قبل المصادقة (login) ومسارات API
     */
    public static function enforceCsrfToken(): void {
        CsrfGraceLogger::checkAndAutoClose();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        // استثناء مسارات التسجيل/إعادة التعيين (تُولَّد التوكنات قبل المصادقة في صفحة الدخول)
        // ملاحظة (بند 3): 'login' أُزيل من الاستثناء — POST /login أصبح يخضع للتحقق من CSRF.
        // ملاحظة (بند 9): 'forgot-password' أُزيل — الإسناد عبر Admin فقط؛ لا مسار/واجهة استعادة (ر.ق 2).
        $uri = $_GET['url'] ?? '';
        $uri = trim($uri, '/');
        $skipRoutes = ['register', 'reset-password'];
        foreach ($skipRoutes as $route) {
            if (strpos($uri, $route) === 0) {
                return;
            }
        }

        // استثناء طلبات API (تستخدم X-API-Key بدلاً من CSRF)
        if (strpos($uri, 'api/') === 0) {
            return;
        }

        $token = $_POST['_token'] ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_CSRFTOKEN'] ?? '';
        if (!self::validateCsrfToken($token)) {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest') || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'],'application/json')!==false) || (!empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !empty($_SERVER['HTTP_X_CSRFTOKEN']));
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(403);
                echo json_encode(['error' => 'انتهت صلاحية الجلسة أو توكن الحماية غير صحيح. يرجى تحديث الصفحة.']);
                exit();
            }
            \App\Core\Flash::set('انتهت صلاحية الجلسة أو توكن الحماية غير صحيح. يرجى تحديث الصفحة والمحاولة مرة أخرى.', 'danger');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? APP_URL . '/evaluations/excel/import'));
            exit();
        }
    }

    // ======================================================================
    // قفل محاولات الدخول (بند 2)
    // ======================================================================

    /** أقصى عدد محاولات فاشلة خلال النافذة قبل القفل */
    public const MAX_LOGIN_ATTEMPTS = 5;

    /** نافذة الحساب بالثواني (15 دقيقة) */
    public const LOCKOUT_WINDOW_SECONDS = 900;

    /**
     * عدد المحاولات الفاشلة المسجلة لاسم المستخدم داخل النافذة الزمنية.
     * @return int
     */
    public static function countLoginFailures(string $username): int {
        if ($username === '') {
            return 0;
        }
        try {
            $db = \App\Core\Database::getInstance();
            $db->query('SELECT COUNT(*) AS c FROM login_attempts
                        WHERE username = :u AND success = 0
                          AND attempted_at > (NOW() - INTERVAL ' . (int)self::LOCKOUT_WINDOW_SECONDS . ' SECOND)');
            $db->bind(':u', $username);
            $row = $db->fetch();
            return $row ? (int)$row->c : 0;
        } catch (\Exception $e) {
            error_log('Auth::countLoginFailures فشل - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * هل الحساب مقفول مؤقتاً (بلغ عتبة المحاولات الفاشلة)؟
     * @return bool
     */
    public static function isLoginLocked(string $username): bool {
        return $username !== '' && self::countLoginFailures($username) >= self::MAX_LOGIN_ATTEMPTS;
    }

    /**
     * تسجيل محاولة دخول (ناجحة/فاشلة) مع عنوان IP في login_attempts.
     */
    public static function logLoginAttempt(string $username, bool $success, ?int $userId = null): void {
        if ($username === '') {
            return;
        }
        try {
            $db = \App\Core\Database::getInstance();
            $db->query('INSERT INTO login_attempts (username, ip_address, success) VALUES (:u, :ip, :ok)');
            $db->bind(':u', $username);
            $db->bind(':ip', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $db->bind(':ok', $success ? 1 : 0);
            $db->execute();
        } catch (\Exception $e) {
            error_log('Auth::logLoginAttempt فشل - ' . $e->getMessage());
        }
    }

    /**
     * مسح سجل محاولات الحساب بعد تسجيل دخول ناجح.
     */
    public static function clearLoginAttempts(string $username): void {
        if ($username === '') {
            return;
        }
        try {
            $db = \App\Core\Database::getInstance();
            $db->query('DELETE FROM login_attempts WHERE username = :u');
            $db->bind(':u', $username);
            $db->execute();
        } catch (\Exception $e) {
            error_log('Auth::clearLoginAttempts فشل - ' . $e->getMessage());
        }
    }

    /**
     * التأخير المتدرج قبل الرد على المحاولة الفاشلة (1، 2، 4، 8 ثوانٍ كحد أقصى).
     * @param int $priorFailures عدد المحاولات الفاشلة قبل هذه المحاولة.
     * @return int
     */
    public static function loginDelaySeconds(int $priorFailures): int {
        $delay = 1 << max(0, min($priorFailures, 30));
        return min($delay, 8);
    }

    /**
     * إخطار المشرفين (Admin) بقفل حساب مؤقت بسبب المحاولات الفاشلة.
     */
    public static function notifyLoginLocked(string $username): void {
        self::notifyAdmins(
            'قفل محاولات الدخول — ' . $username,
            'تم قفل الحساب مؤقتًا بعد ' . self::MAX_LOGIN_ATTEMPTS . ' محاولات دخول فاشلة خلال ' . (int)(self::LOCKOUT_WINDOW_SECONDS / 60) . ' دقيقة.'
        );
    }

    /**
     * إرسال إشعار (type=warning) إلى جميع مستخدمي دور Admin النشطين.
     */
    public static function notifyAdmins(string $title, string $message): void {
        $roleIds = self::adminRoleIds();
        if ($roleIds === []) {
            return;
        }
        try {
            $ph = [];
            foreach (array_keys($roleIds) as $i) {
                $ph[] = ':rid' . $i;
            }
            $in = implode(',', $ph);
            $db = \App\Core\Database::getInstance();
            $db->query("INSERT INTO notifications (user_id, title, message, type, created_at)
                        SELECT id, :title, :message, 'warning', NOW()
                        FROM users
                        WHERE status = 'active'
                        AND id IN (SELECT ur.user_id FROM user_roles ur WHERE ur.role_id IN ($in))");
            $db->bind(':title', $title);
            $db->bind(':message', $message);
            foreach ($roleIds as $i => $rid) {
                $db->bind(':rid' . $i, $rid);
            }
            $db->execute();
        } catch (\Exception $e) {
            error_log('Auth::notifyAdmins فشل - ' . $e->getMessage());
        }
    }

    // ======================================================================
    // بند 14 — دور Admin ديناميكي (بدون معرّف صريح لدور Admin في منطق التشغيل)
    // ======================================================================

    /** كاش معرّفات دور Admin النظامي داخل عملية PHP الواحدة (إعادة الحل في كل طلب). */
    private static $adminRoleIdsCache = null;

    /**
     * معرّفات دور Admin النظامي ديناميكيًا عبر: roles.name = 'Admin' AND is_system_role = 1.
     * @return int[]
     */
    public static function adminRoleIds(): array {
        if (self::$adminRoleIdsCache !== null) {
            return self::$adminRoleIdsCache;
        }
        $ids = [];
        try {
            $ids = \App\Core\Database::getInstance()
                ->query("SELECT id FROM roles WHERE name = 'Admin' AND is_system_role = 1")
                ->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            error_log('Auth::adminRoleIds فشل - ' . $e->getMessage());
        }
        $ids = array_map('intval', $ids);
        if ($ids === []) {
            // Fallback آمن (بند 14): سجّل خطأ، ولا تُرسل إشعارات.
            error_log('CD004_ITEM14: لا يوجد دور Admin نظامي (name=Admin AND is_system_role=1) — لن تُرسل إشعارات المشرفين.');
        }
        self::$adminRoleIdsCache = $ids;
        return $ids;
    }

    /**
     * إعادة تعيين كاش معرّفات دور Admin (تُستخدم عند تغيّر بيانات الأدوار — اختبارات/إعداد).
     */
    public static function clearAdminRoleIdsCache(): void {
        self::$adminRoleIdsCache = null;
    }

    // ======================================================================
    // بند 8 — انعاش نسخة صلاحيات المستخدم (permissions_version)
    // العمود users.permissions_version (مضاف في بند 0) يزداد عند تعديل
    // user_roles / role_permissions عبر مشغلات DB (ترحيل 2026_09_21).
    // في كل طلب: بعد نافذة كاش 60 ثانية تُقارن نسخة الجلسة بنسخة DB،
    // وعند الاختلاف تُبطل الجلسة (إعادة دخول = انعاش تلقائي للصلاحيات).
    // ======================================================================

    /** نافذة الكاش بالثواني قبل قراءة نسخة الصلاحيات من قاعدة البيانات. */
    public const PERMISSIONS_VERSION_CACHE_SECONDS = 60;

    /** مفتاح نسخة الصلاحيات في الجلسة. */
    private const SESSION_PERMS_VERSION = 'permissions_version';

    /** مفتاح وقت آخر فحص للنسخة في الجلسة. */
    private const SESSION_PERMS_CHECK_AT = 'permissions_check_at';

    /**
     * قراءة نسخة الصلاحيات الحالية لمستخدم من قاعدة البيانات.
     * @return int النسخة، أو -1 عند تعذر القراءة (يُعامل كـ«لا تغيير» حفاظًا على التوافر).
     */
    public static function permissionsVersionOf(int $userId): int {
        if ($userId <= 0) {
            return -1;
        }
        try {
            $db = \App\Core\Database::getInstance();
            $db->query('SELECT permissions_version FROM users WHERE id = :uid LIMIT 1');
            $db->bind(':uid', $userId);
            $row = $db->fetch();
            return $row ? (int)$row->permissions_version : -1;
        } catch (\Exception $e) {
            error_log('Auth::permissionsVersionOf فشل - ' . $e->getMessage());
            return -1;
        }
    }

    /**
     * يبني أدوار وصلاحيات المستخدم الحالي في الجلسة ويثبّت نسخة الصلاحيات
     * ووقت الفحص (يُستدعى بعد نجاح الدخول — انعاش صريح في وقت المصادقة).
     */
    public static function refreshSessionPermissions(): void {
        if (!self::check()) {
            return;
        }
        $userId = (int)self::id();
        $userModel = new \App\Models\User();
        $_SESSION['user_roles'] = $userModel->getUserRoles($userId);
        $_SESSION['user_permissions'] = $userModel->getUserPermissions($userId);
        $_SESSION[self::SESSION_PERMS_VERSION] = self::permissionsVersionOf($userId);
        $_SESSION[self::SESSION_PERMS_CHECK_AT] = time();
    }

    /**
     * هل تحتاج صلاحيات الجلسة إلى انعاش (النسخة تغيّرت خارج نافذة الكاش)؟
     * عند خروج النافذة يقرأ النسخة من DB ويقارنها بما في الجلسة.
     * @return bool
     */
    public static function permissionsNeedRefresh(): bool {
        if (!self::check()) {
            return false;
        }
        $lastCheck = (int)($_SESSION[self::SESSION_PERMS_CHECK_AT] ?? 0);
        if ($lastCheck > 0 && (time() - $lastCheck) < self::PERMISSIONS_VERSION_CACHE_SECONDS) {
            return false; // داخل نافذة الكاش — لا قراءة من قاعدة البيانات
        }
        $stored = $_SESSION[self::SESSION_PERMS_VERSION] ?? null;
        $current = self::permissionsVersionOf((int)self::id());
        if ($current < 0) {
            // تعذّرت القراءة — لا نُبطل الجلسة (التوافر قبل صرامة الانعاش)
            $_SESSION[self::SESSION_PERMS_CHECK_AT] = time();
            return false;
        }
        if ($stored === null || (int)$current !== (int)$stored) {
            return true; // اختلاف النسخة (أو جلسة قديمة بلا نسخة) — يستوجب إعادة الدخول
        }
        $_SESSION[self::SESSION_PERMS_CHECK_AT] = time();
        return false;
    }

    /**
     * حارس الطلب (بند 8): إذا تغيّرت نسخة الصلاحيات تُبطل الجلسة
     * وتُوجَّه إعادة الدخول. يُستدعى بعد enforceCsrfToken في public/index.php.
     * ملاحظة: يُستخدم session_unset + session_regenerate_id(true) بدلًا من
     * session_destroy حتى تنتقل رسالة الإعادة إلى صفحة الدخول التالية
     * (التدمير الكامل يُسقط بيانات الجلسة فتُفقد الرسالة) — والأثر الأمني
     * متكافئ: كل بيانات المستخدم تُطهر والمعرّف يُجدَّد.
     */
    public static function invalidateSessionIfPermissionsChanged(): void {
        if (!self::permissionsNeedRefresh()) {
            return;
        }
        session_unset();
        session_regenerate_id(true);
        \App\Core\Flash::set('طرأ تعديل على صلاحيات حسابك — يرجى تسجيل الدخول مجددًا.', 'warning');
        header('Location: ' . APP_URL . '/login');
        exit();
    }

    // ======================================================================
    // سياسة كلمة المرور (بند 11)
    // المعتمدة 2026-09-21 (قرار كبير المستشارين): الطول 8 فأكثر + تنوع 3 من 4
    // (حرف كبير/صغير/رقم/رمز خاص) + رفض آخر 3 كلمات (password_history)
    // + قائمة أعلى 100 كلمة شائعة + بلا انتهاء صلاحية تلقائي.
    // خارطة الترقية المستقبلية: قائمة NIST Breached Passwords عند توفرها + MFA (A1).
    // ======================================================================

    /** الحد الأدنى لطول كلمة المرور. */
    public const PASSWORD_POLICY_MIN_LENGTH = 8;

    /** عدد كلمات المرور السابقة المرفوضة (نبذ التاريخ). */
    public const PASSWORD_POLICY_HISTORY = 3;

    /** القائمة الشائعة (أعلى 100) — مطابقة بحالة الأمرّي (lowercase). */
    private const PASSWORD_POLICY_COMMON = [
        'password', '123456', '123456789', '12345678', '12345', 'qwerty', 'abc123',
        '111111', '1234567', 'password1', 'iloveyou', '123123', 'admin', 'welcome',
        'letmein', 'monkey', 'dragon', '1234567890', 'master', 'sunshine', 'superman',
        'football', '1qaz2wsx', 'trustno1', 'hello', 'cookie', 'charlie', 'aa123456',
        'shadow', '654321', 'michael', 'qwerty123', '123321', 'mustang', 'pa55word',
        'password123', 'batman', '11111111', 'access', 'secret', 'qazwsx', '1q2w3e4r',
        'passw0rd', 'hunter2', 'default', 'asdfgh', 'asdfghjkl', 'qwertyuiop',
        'zaq12wsx', 'qwerty1', '1234', '12345678a', '12345a', '123456789a', '000000',
        '7777777', '88888888', 'letmein1', 'monkey1', 'dragon1', 'welcome1', 'admin123',
        'root', 'toor', '123qwe', '1234qwer', 'zxcvbnm', 'qwerty12345', '999999',
        'a123456', 'abcdef', '1q2w3e', 'pass', 'test', 'test123', 'demo', 'admin1',
        'princess', 'soccer', 'chocolate', 'buster', 'lovely', 'summer', 'winter',
        'starwars', 'naruto', 'pokemon', 'baseball', 'letmein1', 'mickey', 'tigger',
        'jordan', 'cheese', 'computer', 'amanda', 'anthony', 'jennifer', 'michelle',
        'password123!', 'changeme', 'changeme1!', 'temp123!', 'temp123', 'admin123!',
        'welcome1!', 'password1!', '123456789a!A', 'letmein!',
    ];

    /**
     * وصف قواعد السياسة (رسائل توضيحية للواجهة/التوثيق).
     * @return array<string,string>
     */
    public static function passwordPolicyRules(): array {
        return [
            'min_length' => 'الطول الأدنى: ' . self::PASSWORD_POLICY_MIN_LENGTH . ' أحرف.',
            'variety' => 'التنوع: 3 من 4 أنواع (حرف كبير، حرف صغير، رقم، رمز خاص).',
            'history' => 'رفض آخر ' . self::PASSWORD_POLICY_HISTORY . ' كلمات مرور سابقة.',
            'common' => 'الكلمات الأكثر شيوعًا (أعلى 100) غير مسموحة.',
            'expiry' => 'لا انتهاء صلاحية تلقائي (بلا دورة إجبارية للاستبدال).',
            'upgrade' => 'خارطة ترقية مستقبلية: NIST Breached Passwords عند توفرها + MFA (في A1).',
        ];
    }

    /**
     * هل كلمة المرور ضمن آخر 3 كلمات مرور سابقة لهذا المستخدم؟
     */
    public static function passwordReusedRecently(int $userId, string $newPassword): bool {
        try {
            $db = \App\Core\Database::getInstance();
            $db->query('SELECT password_hash FROM password_history
                        WHERE user_id = :uid
                        ORDER BY created_at DESC, id DESC
                        LIMIT ' . (int)self::PASSWORD_POLICY_HISTORY);
            $db->bind(':uid', $userId);
            foreach ($db->fetchAll() ?: [] as $row) {
                if (password_verify($newPassword, $row->password_hash)) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            error_log('Auth::passwordReusedRecently فشل - ' . $e->getMessage());
        }
        return false;
    }

    /**
     * يسجل كلمة المرور السابقة في التاريخ ويُبقي آخر PASSWORD_POLICY_HISTORY فقط.
     */
    public static function recordPasswordHistory(int $userId, string $oldHash): void {
        if ($userId <= 0 || $oldHash === '') {
            return;
        }
        try {
            $db = \App\Core\Database::getInstance();
            $db->query('INSERT INTO password_history (user_id, password_hash) VALUES (:uid, :hash)');
            $db->bind(':uid', $userId);
            $db->bind(':hash', $oldHash);
            $db->execute();
            // إبقاء آخر 3 فقط — جدول مشتق للتغلب على قيد MySQL 1093 (نفس الجدول في subquery)
            $db->query('DELETE FROM password_history
                        WHERE user_id = :uid
                          AND id NOT IN (
                              SELECT id FROM (
                                  SELECT id FROM password_history
                                  WHERE user_id = :uid2
                                  ORDER BY created_at DESC, id DESC
                                  LIMIT ' . (int)self::PASSWORD_POLICY_HISTORY . '
                              ) AS keep_ids
                          )');
            $db->bind(':uid', $userId);
            $db->bind(':uid2', $userId);
            $db->execute();
        } catch (\Exception $e) {
            error_log('Auth::recordPasswordHistory فشل - ' . $e->getMessage());
        }
    }

    /**
     * التحقق الموحّد من سياسة كلمة المرور.
     * @param string|null $password كلمة المرور المرشحة
     * @param int|null $userId معرّف المستخدم للتحقق من نبذ التاريخ (اختياري)
     * @return array{ok: bool, errors: string[]}
     */
    public static function validatePasswordPolicy(?string $password, ?int $userId = null): array {
        $pwd = (string)$password;
        if ($pwd === '') {
            return ['ok' => false, 'errors' => ['كلمة المرور مطلوبة.']];
        }
        $errors = [];

        if (strlen($pwd) < self::PASSWORD_POLICY_MIN_LENGTH) {
            $errors[] = 'يجب أن تكون كلمة المرور ' . self::PASSWORD_POLICY_MIN_LENGTH . ' أحرف على الأقل.';
        }

        $categories = 0;
        if (preg_match('/[a-z]/', $pwd)) { $categories++; }
        if (preg_match('/[A-Z]/', $pwd)) { $categories++; }
        if (preg_match('/[0-9]/', $pwd)) { $categories++; }
        if (preg_match('/[^A-Za-z0-9]/', $pwd)) { $categories++; }
        if ($categories < 3) {
            $errors[] = 'يجب أن تتضمن كلمة المرور 3 من 4 أنواع على الأقل: حرف كبير، حرف صغير، رقم، رمز خاص.';
        }

        if (in_array(strtolower($pwd), self::PASSWORD_POLICY_COMMON, true)) {
            $errors[] = 'هذه الكلمة من القائمة الشائعة (أعلى 100) وغير مسموحة.';
        }

        if ($userId !== null && $userId > 0 && self::passwordReusedRecently($userId, $pwd)) {
            $errors[] = 'لا يمكنك استخدام آخر ' . self::PASSWORD_POLICY_HISTORY . ' كلمات مرور سابقة.';
        }

        return $errors === [] ? ['ok' => true, 'errors' => []] : ['ok' => false, 'errors' => $errors];
    }

    /**
     * مولّد كلمة مرور قوية مطابقة للسياسة (الطول الافتراضي 12، يضمن كأنواع الأربعة).
     */
    public static function generateStrongPassword(int $length = 12): string {
        $length = max(8, $length);
        $lower = 'abcdefghjkmnpqrstuvwxyz';
        $upper = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        $digits = '23456789';
        $special = '!@#$%^&*()-_=+';
        $all = $lower . $upper . $digits . $special;
        do {
            $chars = [
                $upper[random_int(0, strlen($upper) - 1)],
                $lower[random_int(0, strlen($lower) - 1)],
                $digits[random_int(0, strlen($digits) - 1)],
                $special[random_int(0, strlen($special) - 1)],
            ];
            for ($i = 4; $i < $length; $i++) {
                $chars[] = $all[random_int(0, strlen($all) - 1)];
            }
            shuffle($chars);
            $pw = implode('', $chars);
        } while (!self::validatePasswordPolicy($pw)['ok']);
        return $pw;
    }
}
