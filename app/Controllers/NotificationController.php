<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Notification;

/**
 * بند 15 — شاشة الإشعارات.
 * قائمة إشعارات المستخدم + تعليمها كمقروءة (مفرد/الكل) عبر POST محمي CSRF.
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
}