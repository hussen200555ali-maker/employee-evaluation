<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Audit;
use App\Core\Flash;
use App\Models\Notification;

/**
 * بند 15 — شاشة الإشعارات.
 * قائمة إشعارات المستخدم + تعليمها كمقروءة (مفرد/الكل) وحذفها (مفرد/المقروءة/الكل) عبر POST محمي CSRF.
 * ملاحظة: تعليم المقروءة (read/:id و read-all) أُبقي كما هو بلا التحقق من CSRF (سلوك أصلي لم يُمس).
 * مسارات الحذف الجديدة (delete/:id و clear-read و clear-all) تتحقق من CSRF قبل أي تعديل على قاعدة البيانات.
 */
class NotificationController extends Controller {

    public function __construct() {
        if (!Auth::check()) {
            header('Location: ' . APP_URL . '/login');
            exit();
        }
    }

    /** GET /notifications — قائمة إشعارات المستخدم (الأحدث أولًا، مقروء وغير مقروء). */
    public function index() {
        $model = new Notification();
        $userId = (int)Auth::id();
        $data = [
            'pageTitle' => 'الإشعارات',
            'notifications' => $model->forUser($userId),
            'unreadCount' => $model->unreadCount($userId),
        ];
        $this->view('notifications/index', $data);
    }

    /** POST /notifications/read/:id — تعليم إشعار واحد كمقروء. */
    public function markRead($id) {
        (new Notification())->markRead((int)$id, (int)Auth::id());
        $this->redirect('/notifications');
    }

    /** POST /notifications/read-all — تعليم كل الإشعارات. */
    public function markAllRead() {
        (new Notification())->markAllRead((int)Auth::id());
        $this->redirect('/notifications');
    }

    /** POST /notifications/delete/:id — حذف إشعار (الخاص بالمستخدم فقط). */
    public function destroy($id) {
        if (!Auth::validateCsrfToken($_POST['_token'] ?? $_POST['_csrf'] ?? '')) {
            Flash::set('تعذر التحقق من الطلب. أعد المحاولة.', 'danger');
            $this->redirect('/notifications');
            return;
        }
        $userId = (int)Auth::id();
        $deleted = (new Notification())->deleteForUser($userId, (int)$id);
        Audit::log('NOTIFICATION_DELETED', (int)$id, 'notifications', ['scope' => 'single', 'deleted' => $deleted ? 1 : 0]);
        Flash::set($deleted ? 'تم حذف الإشعار.' : 'الإشعار غير موجود أو لا تملك صلاحيته.', $deleted ? 'success' : 'warning');
        $this->redirect('/notifications');
    }

    /** POST /notifications/clear-read — حذف كل الإشعارات المقروءة للمستخدم. */
    public function clearRead() {
        if (!Auth::validateCsrfToken($_POST['_token'] ?? $_POST['_csrf'] ?? '')) {
            Flash::set('تعذر التحقق من الطلب. أعد المحاولة.', 'danger');
            $this->redirect('/notifications');
            return;
        }
        $userId = (int)Auth::id();
        $count = (new Notification())->deleteReadForUser($userId);
        Audit::log('NOTIFICATIONS_CLEAR_READ', $userId, 'notifications', ['scope' => 'read', 'deleted' => $count]);
        Flash::set($count > 0 ? "تم مسح {$count} إشعار مقروء." : 'لا توجد إشعارات مقروءة لحذفها.', $count > 0 ? 'success' : 'info');
        $this->redirect('/notifications');
    }

    /** POST /notifications/clear-all — حذف كل إشعارات المستخدم (مقروءة وغير مقروءة). */
    public function clearAll() {
        if (!Auth::validateCsrfToken($_POST['_token'] ?? $_POST['_csrf'] ?? '')) {
            Flash::set('تعذر التحقق من الطلب. أعد المحاولة.', 'danger');
            $this->redirect('/notifications');
            return;
        }
        $userId = (int)Auth::id();
        $count = (new Notification())->deleteAllForUser($userId);
        Audit::log('NOTIFICATIONS_CLEAR_ALL', $userId, 'notifications', ['scope' => 'all', 'deleted' => $count]);
        Flash::set($count > 0 ? "تم مسح جميع إشعاراتك ({$count})." : 'لا توجد إشعارات لحذفها.', $count > 0 ? 'success' : 'info');
        $this->redirect('/notifications');
    }
}