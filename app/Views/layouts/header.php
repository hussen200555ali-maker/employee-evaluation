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

    <script src="<?php echo APP_URL; ?>/public/libs/chartjs/chart.umd.min.js"></script>
    <script>const BASE_URL = "<?php echo APP_URL; ?>";</script>
    <meta name="csrf-token" content="<?php echo Auth::generateCsrfToken(); ?>">

</head>
<body data-app-url="<?php echo APP_URL; ?>">

<header class="main-header">
    <nav class="navbar navbar-expand-lg navbar-dark navbar-luxury">
        <div class="container-fluid">

            <!-- الشعار -->
            <a class="navbar-brand logo" href="<?php echo APP_URL; ?>">
                <i class="fas fa-chart-line"></i>
                <span><?php echo APP_NAME; ?></span>
            </a>

            <!-- زر الهامبرغر -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- حاوية الروابط -->
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">

                    <?php if (Auth::check()): ?>

                    <!-- ============================================================ -->
                    <!-- 1. لوحة التحكم -->
                    <!-- ============================================================ -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/dashboard">
                            <i class="fas fa-tachometer-alt me-1"></i> لوحة التحكم
                        </a>
                    </li>

                    <!-- ============================================================ -->
                    <!-- 2. الموظفون -->
                    <!-- ============================================================ -->
                    <?php if (Auth::userHasPermission('employees.view') || Auth::userHasPermission('positions.manage')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="employeesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-users me-1"></i> الموظفون
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="employeesDropdown">
                            <?php if (Auth::userHasPermission('employees.view')): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/employees">
                                    <i class="fas fa-list-ul fa-fw me-2"></i> عرض قائمة الموظفين
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if (Auth::userHasPermission('positions.manage')): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/employees/positions">
                                    <i class="fas fa-user-tie fa-fw me-2"></i> إدارة المناصب الوظيفية
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if (Auth::userHasPermission('employees.view') || Auth::userHasPermission('positions.manage')): ?>
                            <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>

                            <?php if (Auth::userHasPermission('employees.create')): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/employees/create">
                                    <i class="fas fa-user-plus fa-fw me-2"></i> إضافة موظف جديد
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- ============================================================ -->
                    <!-- 3. التقييمات -->
                    <!-- ============================================================ -->
                    <?php if (Auth::userHasPermission('evaluations.create') || Auth::userHasPermission('evaluations.fill') || Auth::userHasPermission('reports.view.all')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="evaluationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-clipboard-check me-1"></i> التقييمات
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="evaluationsDropdown">

                            <!-- ——— التقييم الفردي ——— -->
                            <li><h6 class="dropdown-header">التقييم الفردي</h6></li>

                            <?php if (Auth::userHasPermission('evaluations.create')): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/evaluations/create">
                                    <i class="fas fa-play-circle fa-fw me-2"></i> إنشاء تقييم فردي
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if (Auth::userHasPermission('evaluations.fill')): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/evaluations/excel/import">
                                    <i class="fas fa-file-excel fa-fw me-2"></i> استيراد تقييم من Excel
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if (Auth::userHasPermission('reports.view.all')): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/evaluations">
                                    <i class="fas fa-folder-open fa-fw me-2"></i> إدارة التقييمات
                                </a>
                            </li>
                            <?php endif; ?>

                            <!-- ——— فاصل ——— -->
                            <li><hr class="dropdown-divider"></li>

                            <!-- ——— التقييم المؤسسي ——— -->
                            <li><h6 class="dropdown-header">التقييم المؤسسي</h6></li>

                            <?php if (Auth::userHasPermission('units.create')): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/units/evaluations/create">
                                    <i class="fas fa-building fa-fw me-2"></i> تقييم وحدة تنظيمية
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if (Auth::userHasPermission('units.manage')): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/schedules">
                                    <i class="fas fa-calendar-alt fa-fw me-2"></i> الجداول الزمنية للوحدات
                                </a>
                            </li>
                            <?php endif; ?>

                            <!-- ——— فاصل ——— -->
                            <?php if (Auth::userHasPermission('evaluations.create')): ?>
                            <li><hr class="dropdown-divider"></li>

                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/flexible/evaluations/create/course">
                                    <i class="fas fa-chalkboard-teacher fa-fw me-2"></i> تقييم دورة تدريبية
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/flexible/evaluations/create/workshop">
                                    <i class="fas fa-tools fa-fw me-2"></i> تقييم ورشة عمل
                                </a>
                            </li>

                            <!-- ——— فاصل ——— -->
                            <li><hr class="dropdown-divider"></li>

                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/flexible/evaluations/create/employee-in-course">
                                    <i class="fas fa-user-graduate fa-fw me-2"></i> تقييم موظف في دورة
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/flexible/evaluations/create/employee-in-workshop">
                                    <i class="fas fa-user-cog fa-fw me-2"></i> تقييم موظف في ورشة
                                </a>
                            </li>
                            <?php endif; ?>

                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- ============================================================ -->
                    <!-- 4. التدريب -->
                    <!-- ============================================================ -->
                    <?php if (Auth::userHasPermission('training.view')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="trainingDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-graduation-cap me-1"></i> التدريب
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="trainingDropdown">
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/training/courses/catalog">
                                    <i class="fas fa-book fa-fw me-2"></i> كتالوج الدورات التدريبية
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/training/courses">
                                    <i class="fas fa-chalkboard-teacher fa-fw me-2"></i> إدارة الدورات
                                </a>
                            </li>

                            <li><hr class="dropdown-divider"></li>

                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/training/workshops/catalog">
                                    <i class="fas fa-list fa-fw me-2"></i> كتالوج الورش
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/training/workshops">
                                    <i class="fas fa-tools fa-fw me-2"></i> إدارة الورش
                                </a>
                            </li>

                            <li><hr class="dropdown-divider"></li>

                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/archive/courses/branches">
                                    <i class="fas fa-archive fa-fw me-2"></i> أرشيف الدورات
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/archive/workshops/branches">
                                    <i class="fas fa-archive fa-fw me-2"></i> أرشيف الورش
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- ============================================================ -->
                    <!-- 4.5 الجوانب الإدارية (جديد) -->
                    <!-- ============================================================ -->
                    <?php if (Auth::userHasPermission('admin_reports.view')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminReportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-clipboard-list me-1"></i> الجوانب الإدارية
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="adminReportsDropdown">
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/reports">
                                    <i class="fas fa-list fa-fw me-2"></i> التقارير الإدارية
                                </a>
                            </li>
                            <?php if (Auth::userHasPermission('admin_reports.create')): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/reports/create">
                                    <i class="fas fa-plus-circle fa-fw me-2"></i> إنشاء تقرير جديد
                                </a>
                            </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/reports/archive">
                                    <i class="fas fa-archive fa-fw me-2"></i> الأرشيف
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- ============================================================ -->
                    <!-- 5. الهيكل التنظيمي -->
                    <!-- ============================================================ -->
                    <?php if (Auth::userHasPermission('branches.view') || Auth::userHasPermission('departments.view')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="orgStructureDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-network-wired me-1"></i> الهيكل التنظيمي
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="orgStructureDropdown">
                            <?php if (Auth::userHasPermission('branches.view')): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/branches">
                                    <i class="fas fa-building fa-fw me-2"></i> إدارة الفروع
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if (Auth::userHasPermission('departments.view')): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/departments">
                                    <i class="fas fa-sitemap fa-fw me-2"></i> إدارة الأقسام
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- ============================================================ -->
                    <!-- 6. التقارير -->
                    <!-- ============================================================ -->
                    <?php if (Auth::userHasPermission('reports.view.all')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-chart-bar me-1"></i> التقارير
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="reportsDropdown">
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/reports/builder">
                                    <i class="fas fa-cogs fa-fw me-2"></i> بناء تقرير جديد
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item dropdown-item-featured" href="<?php echo APP_URL; ?>/reports/unified">
                                    <i class="fas fa-chart-line fa-fw me-2"></i> التقارير الموحدة
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item dropdown-item-gold" href="<?php echo APP_URL; ?>/reports/statistics">
                                    <i class="fas fa-file-alt fa-fw me-2"></i> التقارير الإحصائية
                                </a>
                            </li>

                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">ملفات الأداء</h6></li>

                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/courses/performance-files">
                                    <i class="fas fa-chalkboard-teacher fa-fw me-2"></i> أداء الدورات
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/workshops/performance-files">
                                    <i class="fas fa-tools fa-fw me-2"></i> أداء الورش
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/units/performance-files">
                                    <i class="fas fa-building fa-fw me-2"></i> أداء الوحدات
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/employees/performance-files">
                                    <i class="fas fa-user-graduate fa-fw me-2"></i> أداء الموظفين في الدورات
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- ============================================================ -->
                    <!-- 7. التحليل الاستراتيجي -->
                    <!-- ============================================================ -->
                    <?php if (Auth::userHasPermission('analysis.view')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/analysis">
                            <i class="fas fa-magic me-1"></i> التحليل الاستراتيجي
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- ============================================================ -->
                    <!-- 8. الإعدادات (Admin + صلاحيات فردية) -->
                    <!-- ============================================================ -->
                    <?php if (Auth::isAdmin() || Auth::userHasPermission('templates.manage') || Auth::userHasPermission('settings.manage') || Auth::userHasPermission('backup.manage')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cogs me-1"></i> الإعدادات
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="settingsDropdown">

                            <?php if (Auth::userHasPermission('templates.manage')): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/templates">
                                    <i class="fas fa-drafting-compass fa-fw me-2"></i> القوالب
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/builder/create">
                                    <i class="fas fa-plus-circle fa-fw me-2"></i> إنشاء قالب جديد
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if (Auth::userHasPermission('settings.manage')): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/settings/rating">
                                    <i class="fas fa-sliders-h fa-fw me-2"></i> إعدادات التصنيف
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if (Auth::userHasPermission('backup.manage')): ?>
                            <li><hr class="dropdown-divider"></li>

                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/backup/dashboard">
                                    <i class="fas fa-database fa-fw me-2"></i> النسخ الاحتياطي
                                </a>
                            </li>
                            <?php endif; ?>

                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- البند 7: إدارة المستخدمين/الأدوار تظهر لمن يملك users.view
                         خارج كتلة «الإعدادات» حتى تظهر لمن لا يملك صلاحيات الإعدادات. -->
                    <?php if (Auth::isAdmin() || Auth::userHasPermission('users.view')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="usersDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-users me-1"></i> المستخدمون
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="usersDropdown">
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/users">
                                    <i class="fas fa-user-shield fa-fw me-2"></i> إدارة المستخدمين
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/roles">
                                    <i class="fas fa-user-tag fa-fw me-2"></i> إدارة الأدوار
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php endif; // Auth::check ?>

                </ul>

                <!-- ============================================================ -->
                <!-- قائمة المستخدم (يمين الشاشة) -->
                <!-- ============================================================ -->
                <div class="d-flex align-items-center ms-3 user-dropdown">
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

            </div>
        </div>
    </nav>
</header>

<main class="main-content">
    <div class="container">
        <?php App\Core\Flash::display(); ?>
