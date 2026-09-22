<?php
/**
 * بند 15 — شاشة الإشعارات.
 * تعرض إشعارات المستخدم كافة (مقروءة وغير مقروءة) الأحدث أولًا،
 * بما فيها الإشعارات القديمة (قبل بند 14) بلا related_url.
 */
require_once APP_ROOT . '/app/Views/layouts/header.php';

$notifications = $data['notifications'] ?? [];
$unreadCount = (int)($data['unreadCount'] ?? 0);

/** خريطة type (قيم enum المعتمدة) → فئة Bootstrap بادج + تسمية عربية — لا تعديل على الـENUM (OOS #1). */
$typeBadge = [
    'info'    => ['bg-info', 'معلومات'],
    'success' => ['bg-success', 'نجاح'],
    'warning' => ['bg-warning', 'تنبيه'],
    'danger'  => ['bg-danger', 'خطر'],
];
?>

<div class="page-header">
    <h2><i class="fas fa-bell"></i> الإشعارات</h2>
    <?php if ($unreadCount > 0): ?>
        <form method="POST" action="<?php echo APP_URL; ?>/notifications/read-all" class="d-inline">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-check-double"></i> تعليم الكل كمقروء
            </button>
        </form>
    <?php endif; ?>
</div>

<p class="lead">
    كل إشعارات حسابك. الإشعارات غير المقروءة تظهر بتمييز واضح.
</p>

<?php if ($notifications === []): ?>
    <div class="alert alert-info">
        <i class="fas fa-bell-slash me-1"></i> لا توجد إشعارات حالياً.
    </div>
<?php else: ?>
    <div class="list-group notification-list">
        <?php foreach ($notifications as $notif):
            [$badgeClass, $badgeLabel] = $typeBadge[$notif['type']] ?? $typeBadge['info'];
            $unread = (int)$notif['is_read'] === 0;
            $url = !empty($notif['related_url'])
                ? htmlspecialchars(APP_URL . $notif['related_url'], ENT_QUOTES, 'UTF-8')
                : '#';
        ?>
            <div class="list-group-item d-flex align-items-start <?php echo $unread ? 'notification-item--unread' : ''; ?>">
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <strong><?php echo htmlspecialchars($notif['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <small class="text-muted"><?php echo htmlspecialchars($notif['created_at'], ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>
                    <p class="mb-1 text-muted"><?php echo nl2br(htmlspecialchars($notif['message'], ENT_QUOTES, 'UTF-8')); ?></p>
                    <div>
                        <span class="badge <?php echo $badgeClass; ?>"><?php echo $badgeLabel; ?></span>
                        <?php if ($url !== '#'): ?>
                            <a class="btn btn-link btn-sm p-0 me-2" href="<?php echo $url; ?>">
                                <i class="fas fa-external-link-alt"></i> فتح
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($unread): ?>
                    <form method="POST" action="<?php echo APP_URL; ?>/notifications/read/<?php echo (int)$notif['id']; ?>" class="m-0 ms-2">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-check"></i> تعليم كمقروء
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>