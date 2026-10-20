<?php
use App\Core\Auth;

// المسار الحالي (بدون استعلام) بعد إزالة بادئة APP_URL — لاستخدامه في تحديد الرابط النشط.
$__currentRaw = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$__basePath = parse_url(APP_URL, PHP_URL_PATH) ?: '';
if ($__basePath !== '' && strpos($__currentRaw, $__basePath) === 0) {
    $__currentRaw = substr($__currentRaw, strlen($__basePath));
}
$__current = '/' . trim($__currentRaw, '/');

$__isActive = static function (string $path) use ($__current): bool {
    $p = '/' . trim($path, '/');
    if ($p === $__current) {
        return true;
    }
    // عدّ الرابط نشطاً أيضاً داخل صفحاته الفرعية (مثال: /employees/create).
    return $p !== '/' && strpos($__current, $p . '/') === 0;
};

// شارة حقيقية: عدد التقييمات قيد المراجعة (تُعرض لمن يملك صلاحية إدارة التقييمات).
$__pendingEvals = null;
if (Auth::check() && Auth::userHasPermission('reports.view.all')) {
    try {
        $__db = \App\Core\Database::getInstance();
        $__db->query("SELECT COUNT(*) FROM evaluations WHERE status = :st");
        $__db->bind(':st', 'pending_review');
        $__pendingEvals = (int)$__db->fetchColumn();
    } catch (\Throwable $e) {
        $__pendingEvals = null;
    }
}

$__user = Auth::user();
$__displayName = $__user->full_name ?? ($__user->username ?? 'مستخدم');
$__roleLabel = $__user->role_name ?? '';
$__avatarLetter = mb_substr(trim((string)$__displayName) !== '' ? (string)$__displayName : 'م', 0, 1, 'UTF-8');
?>
<aside class="app-sidebar" id="appSidebar" aria-label="القائمة الرئيسية">

    <!-- بطاقة المستخدم -->
    <?php if (Auth::check()): ?>
    <div class="sidebar-profile">
        <div class="sidebar-avatar" aria-hidden="true"><?php echo htmlspecialchars($__avatarLetter, ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="sidebar-profile-info">
            <span class="sidebar-profile-name"><?php echo htmlspecialchars($__displayName, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php if ($__roleLabel !== ''): ?>
            <span class="sidebar-profile-role"><?php echo htmlspecialchars($__roleLabel, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- البحث السريع -->
    <div class="sidebar-search">
        <i class="fas fa-search"></i>
        <input type="text" id="sidebarSearch" placeholder="بحث سريع..." aria-label="بحث سريع" autocomplete="off">
    </div>
    <?php endif; ?>

    <nav class="sidebar-nav-wrap">
        <ul class="sidebar-nav">

            <?php if (Auth::check()): ?>

            <!-- ================= الرئيسية ================= -->
            <li class="sidebar-group-title">الرئيسية</li>
            <li>
                <a class="sidebar-link <?php echo $__isActive('/dashboard') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/dashboard">
                    <i class="fas fa-tachometer-alt"></i> لوحة التحكم
                </a>
            </li>

            <!-- ================= الموارد البشرية ================= -->
            <?php if (Auth::userHasPermission('employees.view') || Auth::userHasPermission('positions.manage') || Auth::userHasPermission('branches.view') || Auth::userHasPermission('departments.view')): ?>
            <li class="sidebar-group-title">الموارد البشرية</li>

            <?php if (Auth::userHasPermission('employees.view') || Auth::userHasPermission('positions.manage') || Auth::userHasPermission('employees.create')): ?>
            <li class="sidebar-group <?php echo ($__isActive('/employees')) ? 'open' : ''; ?>">
                <button type="button" class="sidebar-group-toggle" aria-expanded="false">
                    <span class="sidebar-group-label"><i class="fas fa-users sidebar-ico"></i> الموظفون</span>
                    <i class="fas fa-chevron-down sidebar-caret"></i>
                </button>
                <ul class="sidebar-subnav">
                    <?php if (Auth::userHasPermission('employees.view')): ?>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/employees') && !$__isActive('/employees/positions') && !$__isActive('/employees/create') && !$__isActive('/employees/performance-files') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/employees"><i class="fas fa-list-ul fa-fw me-2"></i> عرض قائمة الموظفين</a></li>
                    <?php endif; ?>
                    <?php if (Auth::userHasPermission('positions.manage')): ?>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/employees/positions') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/employees/positions"><i class="fas fa-user-tie fa-fw me-2"></i> إدارة المناصب الوظيفية</a></li>
                    <?php endif; ?>
                    <?php if (Auth::userHasPermission('employees.create')): ?>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/employees/create') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/employees/create"><i class="fas fa-user-plus fa-fw me-2"></i> إضافة موظف جديد</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (Auth::userHasPermission('branches.view') || Auth::userHasPermission('departments.view')): ?>
            <li class="sidebar-group <?php echo ($__isActive('/branches') || $__isActive('/departments')) ? 'open' : ''; ?>">
                <button type="button" class="sidebar-group-toggle" aria-expanded="false">
                    <span class="sidebar-group-label"><i class="fas fa-network-wired sidebar-ico"></i> الهيكل التنظيمي</span>
                    <i class="fas fa-chevron-down sidebar-caret"></i>
                </button>
                <ul class="sidebar-subnav">
                    <?php if (Auth::userHasPermission('branches.view')): ?>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/branches') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/branches"><i class="fas fa-building fa-fw me-2"></i> إدارة الفروع</a></li>
                    <?php endif; ?>
                    <?php if (Auth::userHasPermission('departments.view')): ?>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/departments') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/departments"><i class="fas fa-sitemap fa-fw me-2"></i> إدارة الأقسام</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>
            <?php endif; ?>

            <!-- ================= التقييم والتحليل ================= -->
            <?php if (Auth::userHasPermission('evaluations.create') || Auth::userHasPermission('evaluations.fill') || Auth::userHasPermission('reports.view.all') || Auth::userHasPermission('training.view') || Auth::userHasPermission('analysis.view') || Auth::userHasPermission('admin_reports.view') || Auth::userHasPermission('units.create') || Auth::userHasPermission('units.manage')): ?>
            <li class="sidebar-group-title">التقييم والتحليل</li>

            <?php if (Auth::userHasPermission('evaluations.create') || Auth::userHasPermission('evaluations.fill') || Auth::userHasPermission('reports.view.all') || Auth::userHasPermission('units.create') || Auth::userHasPermission('units.manage')): ?>
            <li class="sidebar-group <?php echo ($__isActive('/evaluations') || $__isActive('/units/evaluations') || $__isActive('/schedules') || $__isActive('/flexible')) ? 'open' : ''; ?>">
                <button type="button" class="sidebar-group-toggle" aria-expanded="false">
                    <span class="sidebar-group-label"><i class="fas fa-clipboard-check sidebar-ico"></i> التقييمات</span>
                    <i class="fas fa-chevron-down sidebar-caret"></i>
                </button>
                <ul class="sidebar-subnav">
                    <li class="sidebar-subheader">التقييم الفردي</li>
                    <?php if (Auth::userHasPermission('evaluations.create')): ?>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/evaluations/create') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/evaluations/create"><i class="fas fa-play-circle fa-fw me-2"></i> إنشاء تقييم فردي</a></li>
                    <?php endif; ?>
                    <?php if (Auth::userHasPermission('evaluations.fill')): ?>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/evaluations/excel/import') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/evaluations/excel/import"><i class="fas fa-file-excel fa-fw me-2"></i> استيراد تقييم من Excel</a></li>
                    <?php endif; ?>
                    <?php if (Auth::userHasPermission('reports.view.all')): ?>
                    <li><a class="sidebar-sublink <?php echo ($__current === '/evaluations') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/evaluations"><i class="fas fa-folder-open fa-fw me-2"></i> إدارة التقييمات<?php if ($__pendingEvals !== null && $__pendingEvals > 0): ?><span class="sidebar-badge"><?php echo (int)$__pendingEvals; ?></span><?php endif; ?></a></li>
                    <?php endif; ?>

                    <?php if (Auth::userHasPermission('units.create') || Auth::userHasPermission('units.manage')): ?>
                    <li class="sidebar-subheader">التقييم المؤسسي</li>
                    <?php endif; ?>
                    <?php if (Auth::userHasPermission('units.create')): ?>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/units/evaluations') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/units/evaluations/create"><i class="fas fa-building fa-fw me-2"></i> تقييم وحدة تنظيمية</a></li>
                    <?php endif; ?>
                    <?php if (Auth::userHasPermission('units.manage')): ?>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/schedules') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/schedules"><i class="fas fa-calendar-alt fa-fw me-2"></i> الجداول الزمنية للوحدات</a></li>
                    <?php endif; ?>

                    <?php if (Auth::userHasPermission('evaluations.create')): ?>
                    <li class="sidebar-subheader">تقييمات مرنة</li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/flexible/evaluations/create/course') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/flexible/evaluations/create/course"><i class="fas fa-chalkboard-teacher fa-fw me-2"></i> تقييم دورة تدريبية</a></li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/flexible/evaluations/create/workshop') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/flexible/evaluations/create/workshop"><i class="fas fa-tools fa-fw me-2"></i> تقييم ورشة عمل</a></li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/flexible/evaluations/create/employee-in-course') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/flexible/evaluations/create/employee-in-course"><i class="fas fa-user-graduate fa-fw me-2"></i> تقييم موظف في دورة</a></li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/flexible/evaluations/create/employee-in-workshop') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/flexible/evaluations/create/employee-in-workshop"><i class="fas fa-user-cog fa-fw me-2"></i> تقييم موظف في ورشة</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (Auth::userHasPermission('training.view')): ?>
            <li class="sidebar-group <?php echo ($__isActive('/training') || $__isActive('/archive')) ? 'open' : ''; ?>">
                <button type="button" class="sidebar-group-toggle" aria-expanded="false">
                    <span class="sidebar-group-label"><i class="fas fa-graduation-cap sidebar-ico"></i> التدريب</span>
                    <i class="fas fa-chevron-down sidebar-caret"></i>
                </button>
                <ul class="sidebar-subnav">
                    <li><a class="sidebar-sublink <?php echo $__isActive('/training/courses/catalog') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/training/courses/catalog"><i class="fas fa-book fa-fw me-2"></i> كتالوج الدورات التدريبية</a></li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/training/courses') && !$__isActive('/training/courses/catalog') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/training/courses"><i class="fas fa-chalkboard-teacher fa-fw me-2"></i> إدارة الدورات</a></li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/training/workshops/catalog') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/training/workshops/catalog"><i class="fas fa-list fa-fw me-2"></i> كتالوج الورش</a></li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/training/workshops') && !$__isActive('/training/workshops/catalog') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/training/workshops"><i class="fas fa-tools fa-fw me-2"></i> إدارة الورش</a></li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/archive/courses') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/archive/courses/branches"><i class="fas fa-archive fa-fw me-2"></i> أرشيف الدورات</a></li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/archive/workshops') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/archive/workshops/branches"><i class="fas fa-archive fa-fw me-2"></i> أرشيف الورش</a></li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (Auth::userHasPermission('admin_reports.view')): ?>
            <li class="sidebar-group <?php echo $__isActive('/admin/reports') ? 'open' : ''; ?>">
                <button type="button" class="sidebar-group-toggle" aria-expanded="false">
                    <span class="sidebar-group-label"><i class="fas fa-clipboard-list sidebar-ico"></i> الجوانب الإدارية</span>
                    <i class="fas fa-chevron-down sidebar-caret"></i>
                </button>
                <ul class="sidebar-subnav">
                    <li><a class="sidebar-sublink <?php echo ($__current === '/admin/reports') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/reports"><i class="fas fa-list fa-fw me-2"></i> التقارير الإدارية</a></li>
                    <?php if (Auth::userHasPermission('admin_reports.create')): ?>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/admin/reports/create') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/reports/create"><i class="fas fa-plus-circle fa-fw me-2"></i> إنشاء تقرير جديد</a></li>
                    <?php endif; ?>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/admin/reports/archive') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/reports/archive"><i class="fas fa-archive fa-fw me-2"></i> الأرشيف</a></li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (Auth::userHasPermission('reports.view.all')): ?>
            <li class="sidebar-group <?php echo ($__isActive('/reports') || $__isActive('/courses/performance-files') || $__isActive('/workshops/performance-files') || $__isActive('/units/performance-files') || $__isActive('/employees/performance-files')) ? 'open' : ''; ?>">
                <button type="button" class="sidebar-group-toggle" aria-expanded="false">
                    <span class="sidebar-group-label"><i class="fas fa-chart-bar sidebar-ico"></i> التقارير</span>
                    <i class="fas fa-chevron-down sidebar-caret"></i>
                </button>
                <ul class="sidebar-subnav">
                    <li><a class="sidebar-sublink <?php echo $__isActive('/reports/builder') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/reports/builder"><i class="fas fa-cogs fa-fw me-2"></i> بناء تقرير جديد</a></li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/reports/unified') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/reports/unified"><i class="fas fa-chart-line fa-fw me-2"></i> التقارير الموحدة</a></li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/reports/statistics') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/reports/statistics"><i class="fas fa-file-alt fa-fw me-2"></i> التقارير الإحصائية</a></li>

                    <li class="sidebar-subheader">التحليل المتقدم</li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/reports/analytics/gap') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/reports/analytics/gap"><i class="fas fa-chart-bar fa-fw me-2"></i> تحليل الفجوات</a></li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/reports/analytics/scorecard') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/reports/analytics/scorecard"><i class="fas fa-clipboard-list fa-fw me-2"></i> بطاقة أداء الموظفين</a></li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/reports/analytics/ninebox') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/reports/analytics/ninebox"><i class="fas fa-th fa-fw me-2"></i> مصفوفة 9-Box</a></li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/reports/analytics/executive') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/reports/analytics/executive"><i class="fas fa-tachometer-alt fa-fw me-2"></i> لوحة القيادة التنفيذية</a></li>

                    <li class="sidebar-subheader">ملفات الأداء</li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/courses/performance-files') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/courses/performance-files"><i class="fas fa-chalkboard-teacher fa-fw me-2"></i> أداء الدورات</a></li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/workshops/performance-files') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/workshops/performance-files"><i class="fas fa-tools fa-fw me-2"></i> أداء الورش</a></li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/units/performance-files') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/units/performance-files"><i class="fas fa-building fa-fw me-2"></i> أداء الوحدات</a></li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/employees/performance-files') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/employees/performance-files"><i class="fas fa-user-graduate fa-fw me-2"></i> أداء الموظفين في الدورات</a></li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (Auth::userHasPermission('analysis.view')): ?>
            <li>
                <a class="sidebar-link <?php echo $__isActive('/analysis') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/analysis">
                    <i class="fas fa-magic"></i> التحليل الاستراتيجي
                </a>
            </li>
            <?php endif; ?>
            <?php endif; ?>

            <!-- ================= الإدارة والنظام ================= -->
            <?php if (Auth::isAdmin() || Auth::userHasPermission('templates.manage') || Auth::userHasPermission('settings.manage') || Auth::userHasPermission('backup.manage') || Auth::userHasPermission('users.view') || Auth::userHasPermission('roles.manage')): ?>
            <li class="sidebar-group-title">الإدارة والنظام</li>

            <?php if (Auth::isAdmin() || Auth::userHasPermission('templates.manage') || Auth::userHasPermission('settings.manage') || Auth::userHasPermission('backup.manage')): ?>
            <li class="sidebar-group <?php echo ($__isActive('/templates') || $__isActive('/builder') || $__isActive('/settings/rating') || $__isActive('/backup')) ? 'open' : ''; ?>">
                <button type="button" class="sidebar-group-toggle" aria-expanded="false">
                    <span class="sidebar-group-label"><i class="fas fa-cogs sidebar-ico"></i> الإعدادات</span>
                    <i class="fas fa-chevron-down sidebar-caret"></i>
                </button>
                <ul class="sidebar-subnav">
                    <?php if (Auth::userHasPermission('templates.manage')): ?>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/templates') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/templates"><i class="fas fa-drafting-compass fa-fw me-2"></i> القوالب</a></li>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/builder/create') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/builder/create"><i class="fas fa-plus-circle fa-fw me-2"></i> إنشاء قالب جديد</a></li>
                    <?php endif; ?>
                    <?php if (Auth::userHasPermission('settings.manage')): ?>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/settings/rating') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/settings/rating"><i class="fas fa-sliders-h fa-fw me-2"></i> إعدادات التصنيف</a></li>
                    <?php endif; ?>
                    <?php if (Auth::userHasPermission('backup.manage')): ?>
                    <li><a class="sidebar-sublink <?php echo $__isActive('/backup') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/backup/dashboard"><i class="fas fa-database fa-fw me-2"></i> النسخ الاحتياطي</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (Auth::isAdmin() || Auth::userHasPermission('users.view')): ?>
            <li>
                <a class="sidebar-link <?php echo $__isActive('/admin/users') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/users">
                    <i class="fas fa-user-shield"></i> المستخدمون
                </a>
            </li>
            <?php endif; ?>

            <?php if (Auth::isAdmin() || Auth::userHasPermission('roles.manage')): ?>
            <li>
                <a class="sidebar-link <?php echo $__isActive('/admin/roles') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/roles">
                    <i class="fas fa-user-tag"></i> الأدوار
                </a>
            </li>
            <?php endif; ?>
            <?php endif; ?>

            <?php endif; // Auth::check ?>

        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-status">
            <span class="sidebar-status-dot" aria-hidden="true"></span>
            <span>متصل</span>
            <span class="sidebar-status-time"><?php echo date('H:i'); ?></span>
        </div>
        <?php if (Auth::check()): ?>
        <form method="POST" action="<?php echo APP_URL; ?>/logout" class="m-0">
            <?php echo csrf_field(); ?>
            <button type="submit" class="sidebar-logout">
                <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
            </button>
        </form>
        <?php endif; ?>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
