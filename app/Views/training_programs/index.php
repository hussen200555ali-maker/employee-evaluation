<?php
/* CD-036.3 : training_programs/index.php — Programs Registry (CRUD + filters) */
require_once APP_ROOT . '/app/Views/layouts/header.php';
require_once APP_ROOT . '/app/Views/layouts/_flash_messages.php';
$programs = $programs ?? []; $filters = $filters ?? []; $branches = $branches ?? []; $departments = $departments ?? [];
$hasFilters = trim($filters['search'] ?? '') !== '' || !empty($filters['type']) || !empty($filters['branch_id']) || !empty($filters['department_id']) || !empty($filters['from_date']) || !empty($filters['to_date']);
$basePath = isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '';
$canManage = App\Core\Auth::userHasPermission('training.manage');
?>
<style>
.tr-card{background:#fff;border:1px solid #e6dfcf;border-radius:12px;padding:22px;margin-bottom:20px;box-shadow:0 1px 3px rgba(12,40,35,.08);}
.tr-title{color:#0c2823;font-size:18px;font-weight:800;margin:0 0 2px;}
.tr-sub{color:#6b6b6b;font-size:13px;margin:0 0 14px;}
.tr-label{font-weight:700;color:#0c2823;font-size:13px;margin-bottom:2px;display:block;}
.tr-input{width:100%;padding:11px 13px;border:1px solid #d4d0c8;border-radius:8px;font-family:'Cairo',sans-serif;font-size:15px;box-sizing:border-box;min-height:42px;background:#fff;color:#1a1a1a;}
.tr-filters{display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end;}
.tr-ff{display:flex;flex-direction:column;}
.tr-note{font-size:12px;color:#8a8a8a;margin-top:10px;}
.tr-btn{padding:10px 18px;border-radius:8px;font-weight:700;cursor:pointer;border:none;font-family:'Cairo',sans-serif;text-decoration:none;display:inline-block;font-size:14px;}
.tr-btn-out{background:transparent;border:1.5px solid #0c2823;color:#0c2823;}
.tr-btn-gold{background:#c8943f;color:#0c2823;}
.tr-table{width:100%;border-collapse:collapse;margin-top:14px;}
.tr-table th{background:#0c2823;color:#fff;padding:11px 12px;text-align:right;font-size:13px;}
.tr-table td{padding:10px 12px;border-bottom:1px solid #e6dfcf;font-size:14px;}
.tr-table tbody tr:nth-child(even) td{background:#faf8f3;}
.tr-table tbody tr:hover td{background:#f0ede4;}
.tr-act{display:inline-flex;gap:12px;}
.tr-act a{color:#0c2823;text-decoration:none;font-size:13px;font-weight:700;cursor:pointer;}
.tr-act a.del{color:#a14b3b;}
.tr-act a.disabled{opacity:.5;cursor:not-allowed;color:#8a8a8a;}
.empty{text-align:center;color:#8a8a8a;padding:18px;}
@media(max-width:640px){.tr-filters{flex-direction:column;align-items:stretch;}.tr-table{font-size:12px;}}
</style>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
    <h2><i class="fas fa-list"></i> 📋 سجل البرامج التدريبية</h2>
    <?php if ($canManage): ?>
        <a class="tr-btn tr-btn-gold tr-btn-sm" href="<?php echo APP_URL; ?>/training/programs/create"><i class="fas fa-plus"></i> ➕ إنشاء برنامج</a>
    <?php endif; ?>
</div>

<div class="tr-card">
    <div class="tr-title">الفلاتر</div>
    <form method="GET" action="" class="tr-filters">
        <div class="tr-ff" style="flex:1 1 200px;min-width:160px"><label class="tr-label">🔍 بحث بالاسم</label><input class="tr-input" type="text" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="اسم البرنامج..."></div>
        <div class="tr-ff" style="flex:1 1 130px;min-width:110px"><label class="tr-label">النوع</label><select class="tr-input" name="type" onchange="this.form.submit()"><option value="">الكل</option><option value="course" <?php echo ($filters['type']??'')==='course'?'selected':''; ?>>دورة</option><option value="workshop" <?php echo ($filters['type']??'')==='workshop'?'selected':''; ?>>ورشة</option></select></div>
        <div class="tr-ff" style="flex:1 1 150px;min-width:130px"><label class="tr-label">🏢 الفرع</label><select class="tr-input" name="branch_id" onchange="this.form.submit()"><option value="">الكل</option><?php foreach ($branches as $b): ?><option value="<?php echo (int)$b->id; ?>" <?php echo ((int)($filters['branch_id']??0)===(int)$b->id)?'selected':''; ?>><?php echo htmlspecialchars($b->name); ?></option><?php endforeach; ?></select></div>
        <div class="tr-ff" style="flex:1 1 150px;min-width:130px"><label class="tr-label">📋 القسم</label><select class="tr-input" name="department_id" onchange="this.form.submit()"><option value="">الكل</option><?php foreach ($departments as $d): ?><option value="<?php echo (int)$d->id; ?>" <?php echo ((int)($filters['department_id']??0)===(int)$d->id)?'selected':''; ?>><?php echo htmlspecialchars($d->name); ?></option><?php endforeach; ?></select></div>
        <div class="tr-ff" style="flex:1 1 140px;min-width:120px"><label class="tr-label">📅 من</label><input class="tr-input" type="date" name="from_date" value="<?php echo htmlspecialchars($filters['from_date'] ?? ''); ?>"></div>
        <div class="tr-ff" style="flex:1 1 140px;min-width:120px"><label class="tr-label">📅 إلى</label><input class="tr-input" type="date" name="to_date" value="<?php echo htmlspecialchars($filters['to_date'] ?? ''); ?>"></div>
        <div class="tr-ff" style="flex:0 0 auto"><button type="submit" class="tr-btn tr-btn-gold">🔍 بحث</button> <a class="tr-btn tr-btn-out tr-btn-sm" href="<?php echo htmlspecialchars($basePath); ?>" style="margin-right:6px">↺ تفريغ</a></div>
    </form>
    <?php if ($hasFilters): ?><div class="tr-note">عرض <?php echo count($programs); ?> برنامج — الفلاتر مفعّلة.</div><?php endif; ?>

    <div class="table-responsive">
        <table class="tr-table">
            <thead><tr><th>#</th><th>اسم البرنامج</th><th>الجهة المنفّذة</th><th>البدء</th><th>الانتهاء</th><th>المقيّمون</th><th>إجراءات</th></tr></thead>
            <tbody>
            <?php foreach ($programs as $i => $p):
                $icon = ($p->type === 'workshop') ? '🏆' : '📚';
                $pc = (int)($p->participant_count ?? 0);
                $pid = (int)$p->id;
                $dept = !empty($p->department_name) ? ' — ' . htmlspecialchars($p->department_name) : '';
            ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo $icon; ?> <?php echo htmlspecialchars($p->title); ?></td>
                    <td><?php echo htmlspecialchars($p->branch_name ?? '—') . $dept; ?></td>
                    <td><?php echo htmlspecialchars($p->start_date ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($p->end_date ?? '—'); ?></td>
                    <td><?php echo $pc; ?></td>
                    <td>
                        <div class="tr-act">
                            <a href="<?php echo APP_URL; ?>/training/programs/<?php echo $pid; ?>">👁️ عرض</a>
                            <?php if ($canManage): ?>
                                <a href="<?php echo APP_URL; ?>/training/programs/<?php echo $pid; ?>/edit">✏️ تعديل</a>
                                <?php if ($pc > 0): ?>
                                    <a class="del disabled" title="لا يمكن الحذف: البرنامج لديه مشاركون مقيّمون" onclick="return false">🗑️ حذف</a>
                                <?php else: ?>
                                    <form method="POST" action="<?php echo APP_URL; ?>/training/programs/<?php echo $pid; ?>/delete" style="display:inline" onsubmit="return confirm('حذف البرنامج «<?php echo htmlspecialchars($p->title, ENT_QUOTES); ?>»؟ لا يمكن التراجع.')">
                                        <?php echo csrf_field(); ?>
                                        <a class="del" href="#" onclick="this.form.submit();return false" title="حذف">🗑️ حذف</a>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$programs): ?><tr><td colspan="7" class="empty"><?php echo $hasFilters ? 'لا توجد برامج مطابقة.' : 'لا توجد برامج بعد — أنشئ برنامجًا.'; ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
