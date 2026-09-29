<?php 
// المسار: app/Views/templates/index.php (النسخة المصححة)
require_once APP_ROOT . '/app/Views/layouts/header.php'; 

?>

<div class="page-header">
    <h2><i class="fas fa-clipboard-list"></i> إدارة قوالب التقييم</h2>
    <?php if (App\Core\Auth::userHasPermission('templates.manage')): ?>
        <a href="<?php echo APP_URL; ?>/builder/create" class="btn-add">
            <i class="fas fa-plus"></i> إنشاء قالب جديد
        </a>
    <?php endif; ?>
</div>

<div class="templates-grid">
    <?php if (!empty($templates)): ?>
        <?php foreach ($templates as $template): ?>
            <div class="template-card">
                <div class="template-card-header">
                    <?php 
                        // ===> الإصلاح 1: التحقق من وجود is_active <===
                        $isActive = isset($template->is_active) && $template->is_active == 1;
                        $statusClass = $isActive ? 'active' : 'draft';
                        $statusText = $isActive ? 'نشط' : 'مسودة';
                        $statusIcon = $isActive ? 'check-circle' : 'hourglass-half';
                    ?>
                    <div class="template-status status-<?php echo $statusClass; ?>">
                        <i class="fas fa-<?php echo $statusIcon; ?>"></i>
                        <span><?php echo $statusText; ?></span>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-start">
                            <li>
                                <!-- زر التعديل الآن يفتح الباني مباشرة -->
                                <a class="dropdown-item" href="<?php echo APP_URL . '/builder/edit/' . $template->id; ?>">
                                    <i class="fas fa-edit"></i> تعديل في الباني
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item js-delete-template" href="#"
                                   data-id="<?php echo $template->id; ?>"
                                   data-name="<?php echo htmlspecialchars($template->template_name ?? ''); // الإصلاح 2 ?>">
                                    <i class="fas fa-trash-alt"></i> حذف القالب
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="template-card-body">
                    <!-- ===> الإصلاح 2: استخدام template_name و template_description <=== -->
                    <h3 class="template-title"><?php echo htmlspecialchars($template->template_name ?? 'قالب بدون اسم'); ?></h3>
                    <p class="template-description"><?php echo htmlspecialchars($template->template_description ?: 'لا يوجد وصف لهذا القالب.'); ?></p>
                </div>
                <div class="template-card-footer">
                    <div class="template-meta">
                        <span><i class="fas fa-layer-group"></i> <?php echo $template->criteria_count ?? 0; ?> معايير</span>
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($template->created_by_name ?? 'غير معروف'); ?></span>
                    </div>
                    <a href="<?php echo APP_URL . '/builder/edit/' . $template->id; ?>" class="btn-manage">
                        <i class="fas fa-drafting-compass"></i> فتح في الباني
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-data-message">
            <i class="fas fa-folder-open"></i>
            <h4>لا توجد قوالب لعرضها حالياً.</h4>
            <p>ابدأ بإنشاء قالب جديد لإعداد تقييماتك.</p>
            <a href="<?php echo APP_URL; ?>/builder/create" class="btn-add">
                <i class="fas fa-plus"></i> إنشاء أول قالب
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- لا حاجة للـ Modals هنا بعد الآن لأننا نستخدم الباني -->

<?php 
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>
