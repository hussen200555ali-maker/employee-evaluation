<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Audit; // ===> الإصلاح 1: استيراد فئة Audit المفقودة

class AuthController extends Controller {

    public function showLoginForm() {
        $this->view('auth/login', ['pageTitle' => 'تسجيل الدخول']);
    }

    public function login() {
        $userModel = new User();

        $username = trim((string)($_POST['username'] ?? ''));

        // ===> بند 2: منع الدخول إذا كان الحساب مقفولاً مؤقتاً (5 محاولات فاشلة خلال 15 دقيقة) <===
        if (Auth::isLoginLocked($username)) {
            Audit::log('USER_LOGIN_LOCKED_BLOCKED', null, 'users', ['username' => $username]);
            Flash::set('تم قفل محاولات الدخول لهذا الحساب مؤقتًا بسبب تعدد المحاولات الفاشلة. حاول بعد 15 دقيقة.', 'danger');
            header('Location: ' . APP_URL . '/login');
            exit();
        }

        // ===> الإصلاح 2: استخدام دالة findById بعد التحقق من كلمة المرور <===
        // أولاً، نتحقق من المستخدم وكلمة المرور باستخدام دالة سريعة
        $userCheck = $userModel->findByUsernameOrEmail($username);

        if ($userCheck && password_verify($_POST['password'], $userCheck->password_hash)) {
            // ===> بند 2: النجاح يمسح سجل المحاولات الفاشلة ويسجل محاولة ناجحة <===
            Auth::clearLoginAttempts($username);
            Auth::logLoginAttempt($username, true, $userCheck->id);
            // كلمة المرور صحيحة!

            // ===> بند 1: فحص حالة الحساب بعد التحقق من كلمة المرور <===
            if ($userCheck->status !== 'active') {
                if ($userCheck->status === 'suspended') {
                    Audit::log('USER_LOGIN_SUSPENDED', $userCheck->id, 'users', ['username' => $userCheck->username]);
                    Auth::notifyAdmins(
                        'محاولة دخول بحساب موقوف — ' . $userCheck->username,
                        'تم رفض محاولة دخول بحساب موقوف (suspended) رغم صحة كلمة المرور.'
                    );
                    Flash::set('تم تعليق حسابك. يرجى التواصل مع مدير النظام.', 'danger');
                } else {
                    // invited — التدفق الكامل (شاشة تعيين كلمة المرور) يؤجل إلى بند 10
                    Audit::log('USER_LOGIN_INVITED', $userCheck->id, 'users', ['username' => $userCheck->username]);
                    Auth::notifyAdmins(
                        'محاولة دخول بحساب غير مفعَّل — ' . $userCheck->username,
                        'تم رفض محاولة دخول بحساب بدعوة غير مفعَّلة (invited) رغم صحة كلمة المرور.'
                    );
                    Flash::set('لم يتم تفعيل حسابك بعد. يرجى التواصل مع مدير النظام.', 'danger');
                }
                header('Location: ' . APP_URL . '/login');
                exit();
            }

            // الآن، نجلب كائن المستخدم الكامل مع كل البيانات (بما في ذلك full_name)
            $user = $userModel->findById($userCheck->id);

            session_unset();
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_object'] = $user; // هذا الكائن يحتوي الآن على full_name
            
            // بند 8: بناء الأدوار والصلاحيات في الجلسة + تثبيت نسخة الصلاحيات ووقت الفحص
            Auth::refreshSessionPermissions();
            
            // تحديث تاريخ آخر تسجيل دخول
            $userModel->updateLastLogin($user->id);
            
            // تسجيل عملية تسجيل الدخول الناجحة
            Audit::log(
                'USER_LOGIN_SUCCESS',
                $user->id,
                'users',
                ['username' => $user->username]
            );

            // ===> بند 9: إلزام تغيير كلمة المرور (must_change_password) قبل المتابعة <===
            if (!empty($user->must_change_password)) {
                Flash::set('يجب تغيير كلمة المرور قبل المتابعة.', 'warning');
                header('Location: ' . APP_URL . '/change-password');
                exit();
            }

            header('Location: ' . APP_URL . '/dashboard');
            exit();
        } else {
            // ===> بند 2: تسجيل المحاولة الفاشلة + التأخير المتدرج + القفل عند العتبة <===
            $priorFailures = Auth::countLoginFailures($username);
            Auth::logLoginAttempt($username, false, $userCheck->id ?? null);

            Audit::log(
                'USER_LOGIN_FAILED',
                null,
                'users',
                ['username_attempt' => $username]
            );

            if (($priorFailures + 1) >= Auth::MAX_LOGIN_ATTEMPTS) {
                Audit::log(
                    'USER_LOGIN_LOCKED',
                    null,
                    'users',
                    ['username' => $username, 'attempts' => $priorFailures + 1]
                );
                Auth::notifyLoginLocked($username);
                Flash::set('تم قفل محاولات الدخول لهذا الحساب مؤقتًا بسبب تعدد المحاولات الفاشلة.', 'danger');
            } else {
                Flash::set('اسم المستخدم أو كلمة المرور غير صحيحة.', 'danger');
            }

            // تأخير متدرج (1/2/4/8 ثوانٍ) قبل الرد لإبطاء التفكيك القسري
            $delay = Auth::loginDelaySeconds($priorFailures);
            if ($delay > 0) {
                sleep($delay);
            }

            header('Location: ' . APP_URL . '/login');
            exit();
        }
    }

    public function logout() {
        Auth::logout(); // session_unset — مسح بيانات الجلسة
        session_destroy();

        // ===> بند 4: إتلاف كوكي الجلسة (EVALSESSID) صراحةً <===
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        header('Location: ' . APP_URL . '/login');
        exit();
    }

    // ===> بند 10: تغيير كلمة المرور (مصادقة مطلوبة) <===

    private function requireLogin(): void
    {
        if (!Auth::check()) {
            Flash::set('يجب تسجيل الدخول أولاً.', 'danger');
            header('Location: ' . APP_URL . '/login');
            exit();
        }
    }

    public function showChangePassword() {
        $this->requireLogin();
        $this->view('auth/change-password', [
            'pageTitle' => 'تغيير كلمة المرور',
            'policy'    => Auth::passwordPolicyRules(),
        ]);
    }

    public function changePassword() {
        $this->requireLogin();

        $userId   = (int)Auth::id();
        $current  = (string)($_POST['current_password'] ?? '');
        $new      = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['password_confirm'] ?? '');

        $userModel = new User();
        $user = $userModel->findById($userId);

        // تأكيد كلمة المرور الحالية (منع تغييرها بحجة جلسة مختطفة — التحقق ذاته يمر عبر hash)
        if (!$user || !password_verify($current, $user->password_hash)) {
            Flash::set('كلمة المرور الحالية غير صحيحة.', 'danger');
            header('Location: ' . APP_URL . '/change-password');
            exit();
        }

        if ($new !== $confirm) {
            Flash::set('تأكيد كلمة المرور الجديدة غير مطابق.', 'danger');
            header('Location: ' . APP_URL . '/change-password');
            exit();
        }

        // بند 11: السياسة + رفض آخر 3 كلمات (بدون علم must_change — نجاح التغيير يمسح العلم)
        if (!$userModel->updatePassword($userId, $new)) {
            $errors = $userModel->lastPasswordErrors ?: ['تعذر تغيير كلمة المرور.'];
            foreach ($errors as $err) {
                Flash::set($err, 'danger');
            }
            header('Location: ' . APP_URL . '/change-password');
            exit();
        }

        Audit::log('USER_PASSWORD_CHANGE', $userId, 'users', ['username' => $user->username]);
        Flash::set('تم تغيير كلمة المرور بنجاح.', 'success');
        header('Location: ' . APP_URL . '/dashboard');
        exit();
    }

    public function csrfToken() {
        header('Content-Type: application/json; charset=utf-8');
        if (!Auth::check()) { http_response_code(401); echo json_encode(['error'=>'غير مصرح']); exit; }
        echo json_encode(['token'=>Auth::generateCsrfToken()]);
        exit;
    }
}
