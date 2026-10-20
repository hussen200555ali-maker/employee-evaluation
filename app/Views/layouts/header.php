<?php
use App\Core\Auth;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' . APP_NAME : APP_NAME; ?></title>

    <!-- 1. ملف تعريف الخطوط المحلية -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/fonts.css">

    <!-- 2. مكتبة الأيقونات Font Awesome -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/libs/fontawesome/css/all.min.css">

    <!-- 3. إطار العمل Bootstrap CSS -->
    <link href="<?php echo APP_URL; ?>/public/libs/bootstrap-5.1.3/css/bootstrap.min.css" rel="stylesheet">

    <!-- 4. مكتبة SweetAlert2 CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/libs/sweetalert2/dist/sweetalert2.min.css">

    <!-- Main Stylesheets -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/style.css?v=2.0">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/modern-ui.css?v=2.0">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/product-ui.css?v=2.0">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/builder.css?v=2.0">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/sidebar.css?v=1.0">

    <script src="<?php echo APP_URL; ?>/public/libs/chartjs/chart.umd.min.js"></script>
    <script>const BASE_URL = "<?php echo APP_URL; ?>";</script>
    <meta name="csrf-token" content="<?php echo Auth::generateCsrfToken(); ?>">

</head>
<body data-app-url="<?php echo APP_URL; ?>">

<div class="app-shell">

    <?php if (Auth::check()): ?>
        <?php require __DIR__ . '/sidebar.php'; ?>
    <?php endif; ?>

    <div class="app-main">

    <header class="thin-header">
        <?php if (Auth::check()): ?>
        <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="فتح القائمة">
            <i class="fas fa-bars"></i>
        </button>
        <?php endif; ?>

        <a class="thin-brand" href="<?php echo APP_URL; ?>">
            <i class="fas fa-chart-line"></i>
            <span><?php echo APP_NAME; ?></span>
        </a>

        <span class="thin-spacer"></span>

        <div class="d-flex align-items-center user-dropdown">
            <?php if (Auth::check()): ?>
                <?php
                // بند 15 — جرس الإشعارات: عدد غير المقروء + أحدث 5 إشعارات.
                $notifModel = new \App\Models\Notification();
                $notifUserId = (int)Auth::id();
                $notifUnread = $notifModel->unreadCount($notifUserId);
                $notifRecent = $notifModel->recentForUser($notifUserId, 5);
                ?>
                <div class="dropdown notification-dropdown ms-2">
                    <a href="#" class="notification-bell" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="الإشعارات">
                        <i class="fas fa-bell"></i>
                        <?php if ($notifUnread > 0): ?>
                            <span class="notification-badge"><?php echo (int)$notifUnread; ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown-menu" aria-labelledby="notificationsDropdown">
                        <li><h6 class="dropdown-header"><i class="fas fa-bell me-1"></i> الإشعارات</h6></li>
                        <?php if ($notifRecent === []): ?>
                            <li><span class="dropdown-item-text text-muted">لا توجد إشعارات.</span></li>
                        <?php else: foreach ($notifRecent as $item): ?>
                            <li>
                                <a class="dropdown-item notification-item <?php echo (int)$item['is_read'] === 0 ? 'notification-item--unread' : ''; ?>"
                                   href="<?php echo !empty($item['related_url']) ? htmlspecialchars(APP_URL . $item['related_url'], ENT_QUOTES, 'UTF-8') : APP_URL . '/notifications'; ?>">
                                    <strong><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <small><?php echo htmlspecialchars($item['message'], ENT_QUOTES, 'UTF-8'); ?></small>
                                </a>
                            </li>
                        <?php endforeach; endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-primary" href="<?php echo APP_URL; ?>/notifications">
                                <i class="fas fa-list me-1"></i> عرض كل الإشعارات
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle user-dropdown-toggle" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="welcome-msg me-2">أهلاً، <?php echo htmlspecialchars(Auth::user()->username ?? 'زائر'); ?></span>
                        <i class="fas fa-user-circle fs-4"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end user-dropdown-menu" aria-labelledby="userMenuDropdown">
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-user-edit fa-fw me-2"></i> تعديل الملف الشخصي
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <!-- بند 4: الخروج عبر POST مع توكن CSRF (يمنع Logout-CSRF) -->
                            <form method="POST" action="<?php echo APP_URL; ?>/logout" class="m-0">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt fa-fw me-2"></i> تسجيل الخروج
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="<?php echo APP_URL; ?>/login" class="btn btn-login-sm">
                    <i class="fas fa-sign-in-alt me-1"></i> تسجيل الدخول
                </a>
            <?php endif; ?>
        </div>
    </header>

<main class="main-content">
    <div class="container">
        <?php App\Core\Flash::display(); ?>
