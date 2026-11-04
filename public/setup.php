<?php
declare(strict_types=1);

$appRoot = dirname(__DIR__);
$configDir = $appRoot . DIRECTORY_SEPARATOR . 'config';
$lockPath = $configDir . DIRECTORY_SEPARATOR . 'installed.lock';

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

function setupEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function setupCsrfToken(): string
{
    if (!isset($_SESSION['setup_csrf'])) {
        $_SESSION['setup_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['setup_csrf'];
}

function setupVerifyCsrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(setupCsrfToken(), $submitted)) {
        http_response_code(400);
        throw new RuntimeException('انتهت صلاحية النموذج. أعد تحميل الصفحة وحاول مجدداً.');
    }
}

function setupPostString(string $key): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        throw new InvalidArgumentException('قيمة نموذج غير صالحة.');
    }

    return $value;
}

function setupConnect(string $host, string $user, string $password, ?string $database = null): PDO
{
    $dsn = 'mysql:host=' . $host . ';charset=utf8mb4';
    if ($database !== null) {
        $dsn .= ';dbname=' . $database;
    }

    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function setupExecuteSqlFile(PDO $pdo, string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        throw new RuntimeException('ملف التثبيت غير موجود أو غير قابل للقراءة.');
    }

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new RuntimeException('تعذر فتح ملف التثبيت.');
    }

    $delimiter = ';';
    $buffer = '';
    try {
        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);
            if (preg_match('/^DELIMITER\s+(\S+)$/i', $trimmed, $matches) === 1) {
                if (trim($buffer) !== '') {
                    throw new RuntimeException('صيغة DELIMITER غير متوقعة في ملف التثبيت.');
                }
                $delimiter = $matches[1];
                continue;
            }

            $buffer .= $line;
            $statement = rtrim($buffer);
            if ($statement !== '' && str_ends_with($statement, $delimiter)) {
                $statement = substr($statement, 0, -strlen($delimiter));
                if (trim($statement) !== '') {
                    $pdo->exec($statement);
                }
                $buffer = '';
            }
        }

        if (trim($buffer) !== '') {
            throw new RuntimeException('انتهى ملف التثبيت وفيه عبارة SQL غير مكتملة.');
        }
    } finally {
        fclose($handle);
    }
}

function setupWriteDatabaseConfig(string $configDir, array $settings): void
{
    if (!is_dir($configDir) || !is_writable($configDir)) {
        throw new RuntimeException('مجلد الإعدادات غير قابل للكتابة.');
    }

    $target = $configDir . DIRECTORY_SEPARATOR . 'database.php';
    if (is_link($target) || (file_exists($target) && !is_file($target))) {
        throw new RuntimeException('مسار ملف إعدادات قاعدة البيانات غير صالح.');
    }

    $contents = "<?php\n"
        . "define('DB_HOST', " . var_export($settings['host'], true) . ");\n"
        . "define('DB_USER', " . var_export($settings['user'], true) . ");\n"
        . "define('DB_PASS', " . var_export($settings['password'], true) . ");\n"
        . "define('DB_NAME', " . var_export($settings['database'], true) . ");\n"
        . "define('DB_CHARSET', 'utf8mb4');\n";

    $temporary = tempnam($configDir, '.database-');
    if ($temporary === false) {
        throw new RuntimeException('تعذر إنشاء ملف إعداد مؤقت.');
    }

    $backup = null;
    try {
        if (file_put_contents($temporary, $contents, LOCK_EX) !== strlen($contents)) {
            throw new RuntimeException('تعذر حفظ إعدادات قاعدة البيانات.');
        }
        chmod($temporary, 0640);

        if (file_exists($target)) {
            $backup = $configDir . DIRECTORY_SEPARATOR . '.database-install-backup';
            if (file_exists($backup) || !rename($target, $backup)) {
                throw new RuntimeException('تعذر استبدال ملف إعدادات قاعدة البيانات بأمان.');
            }
        }

        if (!rename($temporary, $target)) {
            if ($backup !== null && file_exists($backup)) {
                rename($backup, $target);
                $backup = null;
            }
            throw new RuntimeException('تعذر تثبيت ملف إعدادات قاعدة البيانات.');
        }

        if ($backup !== null) {
            unlink($backup);
        }
    } finally {
        if (file_exists($temporary)) {
            unlink($temporary);
        }
        if ($backup !== null && file_exists($backup) && !file_exists($target)) {
            rename($backup, $target);
        }
    }
}

function setupRender(string $title, string $content): void
{
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . setupEscape($title) . '</title>'
        . '<style>body{font:16px/1.7 system-ui,sans-serif;background:#f4f6f8;color:#17212b;margin:0;padding:2rem}'
        . 'main{max-width:680px;margin:4vh auto;background:white;padding:2rem;border-radius:12px;box-shadow:0 8px 30px #17212b18}'
        . 'label{display:block;font-weight:600;margin:.8rem 0 .25rem}input{box-sizing:border-box;width:100%;padding:.7rem;border:1px solid #aeb8c2;border-radius:6px}'
        . 'button,a.button{display:inline-block;margin-top:1.2rem;padding:.7rem 1.2rem;background:#155eef;color:white;border:0;border-radius:6px;text-decoration:none;cursor:pointer}'
        . '.error{padding:.8rem;background:#fff0f0;color:#9b1c1c;border-radius:6px}.muted{color:#56616d}</style>'
        . '<body><main><h1>' . setupEscape($title) . '</h1>' . $content . '</main></body></html>';
}

function setupHiddenFields(): string
{
    return '<input type="hidden" name="csrf_token" value="' . setupEscape(setupCsrfToken()) . '">';
}

if (is_file($lockPath)) {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && ($_GET['step'] ?? '') === '4') {
        setupRender('اكتمل التثبيت', '<p>تم تثبيت النظام بنجاح. معالج التثبيت مغلق الآن.</p>'
            . '<a class="button" href="../">الانتقال إلى النظام</a>');
    } else {
        http_response_code(403);
        setupRender('معالج التثبيت مغلق', '<p>التطبيق مثبت بالفعل. لا يمكن تشغيل المعالج مرة أخرى.</p>');
    }
    exit;
}

$step = filter_input(INPUT_GET, 'step', FILTER_VALIDATE_INT);
$step = $step === false || $step === null ? 1 : $step;
$error = '';
$message = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $postedStep = filter_input(INPUT_POST, 'step', FILTER_VALIDATE_INT);
    try {
        setupVerifyCsrf();

        if ($postedStep === 2) {
            $host = trim(setupPostString('db_host'));
            $user = trim(setupPostString('db_user'));
            $password = setupPostString('db_password');
            $database = trim(setupPostString('db_name'));

            if ($host === '' || strlen($host) > 253
                || preg_match('/^[A-Za-z0-9.-]+$/', $host) !== 1
                || $user === '' || strlen($user) > 64
                || preg_match('/^[A-Za-z0-9_]+$/', $database) !== 1
                || strlen($database) > 64) {
                throw new InvalidArgumentException('تحقق من اسم المضيف واسم المستخدم واسم قاعدة البيانات.');
            }

            $server = setupConnect($host, $user, $password);
            $quotedDatabase = '`' . $database . '`';
            $server->exec('CREATE DATABASE IF NOT EXISTS ' . $quotedDatabase
                . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
            $db = setupConnect($host, $user, $password, $database);
            $tableCount = (int)$db->query(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
            )->fetchColumn();
            if ($tableCount !== 0) {
                throw new RuntimeException('قاعدة البيانات المحددة ليست فارغة؛ لم يتم تعديل محتواها.');
            }

            setupExecuteSqlFile($db, $appRoot . DIRECTORY_SEPARATOR . 'setup'
                . DIRECTORY_SEPARATOR . 'install_schema.sql');
            setupExecuteSqlFile($db, $appRoot . DIRECTORY_SEPARATOR . 'setup'
                . DIRECTORY_SEPARATOR . 'install_seeds.sql');

            setupWriteDatabaseConfig($configDir, [
                'host' => $host,
                'user' => $user,
                'password' => $password,
                'database' => $database,
            ]);

            $_SESSION['setup_db'] = [
                'host' => $host,
                'user' => $user,
                'password' => $password,
                'database' => $database,
            ];
            session_regenerate_id(true);
            header('Location: ?step=3', true, 303);
            exit;
        }

        if ($postedStep === 3) {
            $settings = $_SESSION['setup_db'] ?? null;
            if (!is_array($settings)) {
                throw new RuntimeException('انتهت جلسة التثبيت؛ ابدأ المعالج من جديد.');
            }

            $username = trim(setupPostString('admin_username'));
            $email = trim(setupPostString('admin_email'));
            $password = setupPostString('admin_password');
            $confirmation = setupPostString('admin_password_confirmation');

            if (preg_match('/^[A-Za-z0-9_.-]{3,100}$/', $username) !== 1) {
                throw new InvalidArgumentException('اسم المستخدم يجب أن يتكون من 3 إلى 100 محرف لاتيني أو رقم أو . _ -');
            }
            if (strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new InvalidArgumentException('أدخل بريداً إلكترونياً صالحاً.');
            }
            if (!hash_equals($password, $confirmation)) {
                throw new InvalidArgumentException('تأكيد كلمة المرور غير مطابق.');
            }
            require_once $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR
                . 'Core' . DIRECTORY_SEPARATOR . 'Auth.php';
            $policy = \App\Core\Auth::validatePasswordPolicy($password);
            if (!$policy['ok']) {
                throw new InvalidArgumentException(implode(' ', $policy['errors']));
            }

            $lock = fopen($lockPath, 'x');
            if ($lock === false) {
                throw new RuntimeException('تعذر إنشاء قفل التثبيت؛ تحقق من صلاحيات مجلد config.');
            }
            $lockOwned = true;
            $installed = false;
            try {
                if (fwrite($lock, "installation in progress\n") === false || !fflush($lock)) {
                    throw new RuntimeException('تعذر كتابة قفل التثبيت.');
                }

                $db = setupConnect(
                    $settings['host'],
                    $settings['user'],
                    $settings['password'],
                    $settings['database']
                );
                $roleId = $db->query("SELECT id FROM roles WHERE name = 'Admin' AND is_system_role = 1")
                    ->fetchColumn();
                if ($roleId === false) {
                    throw new RuntimeException('دور Admin الأساسي غير موجود في بيانات التهيئة.');
                }

                $db->beginTransaction();
                $statement = $db->prepare(
                    "INSERT INTO users (username, email, password_hash, status, must_change_password)
                     VALUES (:username, :email, :password_hash, 'active', 1)"
                );
                $statement->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                $userId = (int)$db->lastInsertId();
                $statement = $db->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)');
                $statement->execute([':user_id' => $userId, ':role_id' => (int)$roleId]);
                $db->commit();
                $installed = true;

                ftruncate($lock, 0);
                rewind($lock);
                fwrite($lock, 'installed ' . gmdate(DATE_ATOM) . "\n");
                fflush($lock);
                chmod($lockPath, 0640);
                fclose($lock);
                $lockOwned = false;

                unset($_SESSION['setup_db'], $_SESSION['setup_csrf']);
                session_regenerate_id(true);
                header('Location: ?step=4', true, 303);
                exit;
            } catch (Throwable $exception) {
                if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
                    $db->rollBack();
                }
                if (is_resource($lock)) {
                    fclose($lock);
                }
                if ($lockOwned && !$installed && is_file($lockPath)) {
                    unlink($lockPath);
                }
                throw $exception;
            }
        }

        throw new InvalidArgumentException('خطوة التثبيت غير صالحة.');
    } catch (Throwable $exception) {
        error_log('Setup wizard failure: ' . $exception->getMessage());
        $error = $exception instanceof InvalidArgumentException
            ? $exception->getMessage()
            : 'تعذر إكمال هذه الخطوة. تحقق من بيانات الاتصال وصلاحيات الملفات، ثم راجع سجل أخطاء PHP.';
        $step = $postedStep === 3 ? 3 : 2;
    }
}

if ($step < 1 || $step > 4) {
    http_response_code(404);
    setupRender('خطوة غير موجودة', '<p>خطوة التثبيت المطلوبة غير موجودة.</p>');
    exit;
}

if ($step === 1) {
    setupRender('تثبيت نظام تقييم الموظفين', '<p>معالج التثبيت يتكون من أربع خطوات: التحقق من المتطلبات، إعداد قاعدة البيانات، إنشاء حساب المدير، ثم التأكيد.</p>'
        . '<p class="muted">يلزم اتصال MySQL/MariaDB ومجلد config قابل للكتابة. لا تستخدم قاعدة بيانات تحتوي على بيانات.</p>'
        . '<a class="button" href="?step=2">بدء التثبيت</a>');
    exit;
}

if ($step === 2) {
    $errorHtml = $error === '' ? '' : '<p class="error">' . setupEscape($error) . '</p>';
    setupRender('الخطوة 2 من 4: إعداد قاعدة البيانات', $errorHtml
        . '<form method="post" autocomplete="off">'
        . '<input type="hidden" name="step" value="2">' . setupHiddenFields()
        . '<label for="db_host">مضيف قاعدة البيانات</label><input id="db_host" name="db_host" value="localhost" required>'
        . '<label for="db_name">اسم قاعدة البيانات الجديدة</label><input id="db_name" name="db_name" value="employee_evaluation_mvc" required>'
        . '<label for="db_user">مستخدم قاعدة البيانات</label><input id="db_user" name="db_user" required>'
        . '<label for="db_password">كلمة مرور قاعدة البيانات</label><input id="db_password" name="db_password" type="password" autocomplete="new-password">'
        . '<button type="submit">إنشاء المخطط والمتابعة</button></form>');
    exit;
}

if ($step === 3) {
    if (!isset($_SESSION['setup_db']) || !is_array($_SESSION['setup_db'])) {
        setupRender('جلسة التثبيت غير متاحة', '<p>ابدأ من الخطوة الأولى في الجلسة نفسها لإعداد قاعدة البيانات.</p>'
            . '<a class="button" href="?step=1">العودة للبداية</a>');
        exit;
    }
    $errorHtml = $error === '' ? '' : '<p class="error">' . setupEscape($error) . '</p>';
    setupRender('الخطوة 3 من 4: إنشاء المدير', $errorHtml
        . '<p>ستكون كلمة المرور مطلوبة للتغيير عند أول تسجيل دخول.</p>'
        . '<form method="post" autocomplete="off">'
        . '<input type="hidden" name="step" value="3">' . setupHiddenFields()
        . '<label for="admin_username">اسم المستخدم</label><input id="admin_username" name="admin_username" required minlength="3" maxlength="100">'
        . '<label for="admin_email">البريد الإلكتروني</label><input id="admin_email" name="admin_email" type="email" required maxlength="255">'
        . '<label for="admin_password">كلمة المرور</label><input id="admin_password" name="admin_password" type="password" required autocomplete="new-password">'
        . '<label for="admin_password_confirmation">تأكيد كلمة المرور</label><input id="admin_password_confirmation" name="admin_password_confirmation" type="password" required autocomplete="new-password">'
        . '<button type="submit">إنشاء المدير وإنهاء التثبيت</button></form>');
    exit;
}

if (!is_file($lockPath)) {
    http_response_code(409);
    setupRender('التثبيت غير مكتمل', '<p>لا يوجد قفل تثبيت؛ أكمل الخطوات السابقة أولاً.</p>'
        . '<a class="button" href="?step=1">العودة للبداية</a>');
    exit;
}

setupRender('الخطوة 4 من 4: اكتمل التثبيت', '<p>تم إعداد قاعدة البيانات وإنشاء حساب المدير. معالج التثبيت مغلق الآن.</p>'
    . '<a class="button" href="../">الانتقال إلى النظام</a>');
