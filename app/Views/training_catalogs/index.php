<?php
/* CD-036 : training_catalogs/index.php — Screen 1 (manage catalog) */
require_once APP_ROOT . '/app/Views/layouts/header.php';
require_once APP_ROOT . '/app/Views/layouts/_flash_messages.php';
$catalogs = $catalogs ?? [];
$typeLabel = ['course' => 'دورة', 'workshop' => 'ورشة', 'both' => 'كلاهما'];
?>
<style>
.tr-card{background:#fff;border:1px solid #e6dfcf;border-radius:12px;padding:22px;margin-bottom:20px;box-shadow:0 1px 3px rgba(12,40,35,.08);}
.tr-title{color:#0c2823;font-size:18px;font-weight:800;margin:0 0 2px;}
.tr-sub{color:#6b6b6b;font-size:13px;margin:0 0 16px;}
.tr-label{font-weight:700;color:#0c2823;font-size:14px;margin-bottom:4px;display:block;}
.tr-input{width:100%;padding:11px 13px;border:1px solid #d4d0c8;border-radius:8px;font-family:'Cairo',sans-serif;font-size:15px;box-sizing:border-box;min-height:42px;background:#fff;color:#1a1a1a;}
.tr-input:focus{outline:none;border-color:#c8943f;box-shadow:0 0 0 3px rgba(200,148,63,.15);}
.tr-grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.tr-btn{padding:10px 20px;border-radius:8px;font-weight:700;cursor:pointer;border:none;font-family:'Cairo',sans-serif;text-decoration:none;display:inline-block;font-size:14px;}
.tr-btn-gold{background:#c8943f;color:#0c2823;}
.tr-btn-out{background:transparent;border:1.5px solid #0c2823;color:#0c2823;}
.tr-btn-sm{padding:7px 14px;font-size:13px;}
.tr-table{width:100%;border-collapse:collapse;margin-top:12px;}
.tr-table th{background:#0c2823;color:#fff;padding:11px 12px;text-align:right;font-size:13px;}
.tr-table td{padding:10px 12px;border-bottom:1px solid #e6dfcf;font-size:14px;}
.tr-table tbody tr:nth-child(even) td{background:#faf8f3;}
.tr-table tbody tr:hover td{background:#f0ede4;}
.tr-tag{padding:2px 9px;border-radius:999px;font-size:12px;font-weight:700;}
.tr-ok{background:rgba(200,148,63,.16);color:#8a6420;}.tr-no{background:rgba(107,107,107,.14);color:#6b6b6b;}
.tr-act{display:inline-flex;gap:10px;}
.tr-act a{color:#0c2823;text-decoration:none;font-size:13px;font-weight:700;cursor:pointer;}
.tr-act a.del{color:#a14b3b;}
.req{color:#c8943f;}
@media(max-width:640px){.tr-grid2{grid-template-columns:1fr;}}
</style>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
    <h2><i class="fas fa-folder-open"></i> 📚 كتالوج التصنيفات</h2>
    <?php if (App\Core\Auth::userHasPermission('training.manage')): ?>
        <a class="tr-btn tr-btn-gold tr-btn-sm" href="#catForm">➕ إضافة تصنيف</a>
    <?php endif; ?>
</div>

<div class="tr-card">
    <div class="tr-title">التصنيفات الحالية</div>
    <div class="tr-sub">توحيد تسميات الدورات والورش عبر الفروع والأقسام — <?php echo count($catalogs); ?> تصنيف</div>
    <div class="table-responsive">
        <table class="tr-table">
            <thead><tr><th>#</th><th>اسم التصنيف</th><th>النوع</th><th>التصنيف</th><th>الحالة</th><th>إجراءات</th></tr></thead>
            <tbody>
            <?php foreach ($catalogs as $i => $c): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo htmlspecialchars($c->name); ?></td>
                    <td><?php echo $typeLabel[$c->type] ?? $c->type; ?></td>
                    <td><?php echo htmlspecialchars($c->category ?? '—'); ?></td>
                    <td><span class="tr-tag <?php echo $c->status === 'active' ? 'tr-ok' : 'tr-no'; ?>"><?php echo $c->status === 'active' ? 'نشط' : 'موقوف'; ?></span></td>
                    <td>
                        <div class="tr-act">
                            <?php if (App\Core\Auth::userHasPermission('training.manage')): ?>
                                <a href="#" onclick='editCat(<?php echo json_encode(["id"=>(int)$c->id,"name"=>$c->name,"type"=>$c->type,"description"=>$c->description??"","category"=>$c->category??"","status"=>$c->status], JSON_UNESCAPED_UNICODE); ?>);return false'>✏️ تعديل</a>
                                <form method="POST" action="<?php echo APP_URL; ?>/training/catalogs/<?php echo (int)$c->id; ?>/delete" style="display:inline" onsubmit="return confirm('حذف التصنيف «<?php echo htmlspecialchars($c->name, ENT_QUOTES); ?>»؟')">
                                    <?php echo csrf_field(); ?>
                                    <a class="del" href="#" onclick="this.closest('form').requestSubmit();return false">🗑️ حذف</a>
                                </form>
                            <?php else: ?><span style="color:#8a8a8a">—</span><?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$catalogs): ?><tr><td colspan="6" style="text-align:center;color:#8a8a8a;padding:22px">لا توجد تصنيفات بعد.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (App\Core\Auth::userHasPermission('training.manage')): ?>
<div class="tr-card" id="catForm">
    <div class="tr-title" id="formTitle">📂 إضافة تصنيف جديد</div>
    <form id="catalogForm" method="POST" action="<?php echo APP_URL; ?>/training/catalogs/store">
        <?php echo csrf_field(); ?>
        <div class="tr-grid2">
            <div><label class="tr-label">اسم التصنيف <span class="req">*</span></label><input class="tr-input" name="name" id="f_name" required placeholder="مثال: إدارة المشاريع"></div>
            <div><label class="tr-label">النوع <span class="req">*</span></label><select class="tr-input" name="type" id="f_type"><option value="course">دورة</option><option value="workshop">ورشة</option><option value="both">كلاهما</option></select></div>
            <div><label class="tr-label">التصنيف (category)</label><input class="tr-input" name="category" id="f_category" placeholder="إدارية / فنية / سلامة..."></div>
            <div><label class="tr-label">الحالة</label><select class="tr-input" name="status" id="f_status"><option value="active">نشط</option><option value="inactive">موقوف</option></select></div>
        </div>
        <div style="margin-top:14px"><label class="tr-label">الوصف</label><textarea class="tr-input" name="description" id="f_description" rows="2"></textarea></div>
        <div style="margin-top:16px;text-align:left">
            <button class="tr-btn tr-btn-gold" type="submit" id="submitBtn">حفظ التصنيف</button>
            <a class="tr-btn tr-btn-out tr-btn-sm" href="#" onclick="resetForm();return false" style="margin-right:8px">إلغاء</a>
        </div>
    </form>
</div>
<script>
function editCat(d){
    document.getElementById('formTitle').textContent='✏️ تعديل: '+d.name;
    document.getElementById('catalogForm').action=BASE_URL+'/training/catalogs/'+d.id+'/update';
    document.getElementById('f_name').value=d.name; document.getElementById('f_type').value=d.type;
    document.getElementById('f_category').value=d.category||''; document.getElementById('f_status').value=d.status;
    document.getElementById('f_description').value=d.description||'';
    document.getElementById('submitBtn').textContent='حفظ التعديلات';
    document.getElementById('catForm').scrollIntoView({behavior:'smooth'});
}
function resetForm(){
    document.getElementById('formTitle').textContent='📂 إضافة تصنيف جديد';
    document.getElementById('catalogForm').action=BASE_URL+'/training/catalogs/store';
    document.getElementById('catalogForm').reset(); document.getElementById('submitBtn').textContent='حفظ التصنيف';
}
</script>
<?php endif; ?>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
