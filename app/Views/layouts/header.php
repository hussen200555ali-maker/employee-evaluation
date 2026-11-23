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
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/style.css?v=2.6">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/modern-ui.css?v=2.2">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/product-ui.css?v=2.2">
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

        <span class="thin-spacer"></span>

        <div class="d-flex align-items-center user-dropdown">
            <?php if (Auth::check()): ?>
                <?php
                // بند 15 — جرس الإشعارات: عدد غير المقروء + أحدث 5 إشعارات.
                $notifModel = new \App\Models\Notification();
                $notifUserId = (int)Auth::id();
                $notifUnread = $notifModel->unreadCount($notifUserId);
                $notifRecent = $notifModel->recentForUser($notifUserId, 5);
                // v23: أيقونة + لون لكل نوع (نفس هوية صفحة الإشعارات) + زمن نسبي عربي
                $notifTypeIconMap = [
                    'info'    => ['fa-circle-info', 'nd-icon--info'],
                    'success' => ['fa-circle-check', 'nd-icon--success'],
                    'warning' => ['fa-triangle-exclamation', 'nd-icon--warning'],
                    'danger'  => ['fa-circle-xmark', 'nd-icon--danger'],
                ];
                $notifRelTime = function ($value) {
                    $ts = strtotime((string)$value);
                    if ($ts === false) return (string)$value;
                    $diff = max(0, time() - $ts);
                    if ($diff < 60) return 'الآن';
                    if ($diff < 3600) return 'منذ ' . (int)floor($diff / 60) . ' د';
                    if ($diff < 86400) return 'منذ ' . (int)floor($diff / 3600) . ' س';
                    if ($diff < 604800) return 'منذ ' . (int)floor($diff / 86400) . ' أيام';
                    return date('Y-m-d', $ts);
                };
                ?>
                <div class="dropdown notification-dropdown ms-2">
                    <a href="#" class="notification-bell" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="الإشعارات<?php echo $notifUnread > 0 ? ' — ' . (int)$notifUnread . ' غير مقروء' : ''; ?>" aria-label="الإشعارات<?php echo $notifUnread > 0 ? ' — ' . (int)$notifUnread . ' غير مقروء' : ''; ?>">
                        <i class="fas fa-bell"></i>
                        <?php if ($notifUnread > 0): ?>
                            <span class="notification-badge"><?php echo (int)$notifUnread; ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown-menu" aria-labelledby="notificationsDropdown">
                        <li class="notification-dropdown-header">
                            <span class="notification-dropdown-header__title">
                                <span class="notification-dropdown-header__icon"><i class="fas fa-bell"></i></span>
                                <span class="notification-dropdown-header__text">
                                    <strong>مركز الإشعارات</strong>
                                    <small>أحدث <?php echo count($notifRecent); ?> — حديثة أولًا</small>
                                </span>
                            </span>
                            <?php if ($notifUnread > 0): ?>
                                <span class="notification-dropdown-header__badge"><?php echo (int)$notifUnread; ?> جديد</span>
                            <?php endif; ?>
                        </li>
                        <?php if ($notifRecent === []): ?>
                            <li class="notification-dropdown-empty">
                                <i class="fas fa-envelope-open-text" aria-hidden="true"></i>
                                <p>لا توجد إشعارات حالياً</p>
                                <small>ستظهر هنا الأخبار المهمة فور وصولها</small>
                            </li>
                        <?php else: foreach ($notifRecent as $item):
                            $t = (string)($item['type'] ?? 'info');
                            [$tIcon, $tClass] = $notifTypeIconMap[$t] ?? $notifTypeIconMap['info'];
                            $isUnread = (int)$item['is_read'] === 0;
                            $itemUrl = !empty($item['related_url']) ? htmlspecialchars(APP_URL . $item['related_url'], ENT_QUOTES, 'UTF-8') : APP_URL . '/notifications';
                        ?>
                            <li>
                                <a class="notification-item<?php echo $isUnread ? ' notification-item--unread' : ''; ?>" href="<?php echo $itemUrl; ?>">
                                    <span class="notification-item__icon <?php echo $tClass; ?>" aria-hidden="true"><i class="fas <?php echo htmlspecialchars($tIcon, ENT_QUOTES, 'UTF-8'); ?>"></i></span>
                                    <span class="notification-item__body">
                                        <span class="notification-item__row">
                                            <strong><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <?php if ($isUnread): ?><span class="notification-item__dot" title="غير مقروء" aria-label="غير مقروء"></span><?php endif; ?>
                                        </span>
                                        <small class="notification-item__msg"><?php echo htmlspecialchars($item['message'], ENT_QUOTES, 'UTF-8'); ?></small>
                                        <span class="notification-item__meta"><i class="fas fa-clock" aria-hidden="true"></i><?php echo htmlspecialchars($notifRelTime($item['created_at']), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; endif; ?>
                        <?php if ($notifUnread > 0): ?>
                            <li class="notification-dropdown-markall">
                                <form method="POST" action="<?php echo APP_URL; ?>/notifications/read-all" class="m-0">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="notification-dropdown-markall__btn"><i class="fas fa-check-double" aria-hidden="true"></i> تعليم الكل كمقروء</button>
                                </form>
                            </li>
                        <?php endif; ?>
                        <li class="notification-dropdown-footer">
                            <a class="notification-dropdown-footer__link" href="<?php echo APP_URL; ?>/notifications">
                                <span><i class="fas fa-list-ul" aria-hidden="true"></i> عرض كل الإشعارات</span>
                                <i class="fas fa-arrow-left notification-dropdown-footer__arrow" aria-hidden="true"></i>
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
