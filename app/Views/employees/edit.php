<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<style>
.emp-form-hero{background:linear-gradient(135deg,#0b5ed7 0%,#0dcaf0 55%,#20c997 100%);border-radius:20px;color:#fff;position:relative;overflow:hidden;box-shadow:0 12px 40px rgba(13,110,253,.25)}
.emp-form-hero::after{content:'';position:absolute;inset:0;background:radial-gradient(520px 180px at 85% 10%, rgba(255,255,255,.22), transparent 60%)}
.form-card{background:#fff;border:1px solid #eef1f6;border-radius:16px;box-shadow:0 8px 28px rgba(0,0,0,.06);overflow:hidden}
.form-card .card-head{padding:1rem 1.25rem;border-bottom:1px solid #f0f2f7;background:linear-gradient(180deg,#f8f9ff,#fff);display:flex;align-items:center;gap:.6rem;font-weight:800}
.form-card .card-body{padding:1.5rem}
.form-floating-custom{position:relative}
.form-floating-custom input,.form-floating-custom select{border-radius:12px;border:1px solid #e9ecef;padding:.85rem 1rem .85rem 2.5rem;transition:.2s;background:#fbfcff;width:100%}
.form-floating-custom input:focus,.form-floating-custom select:focus{border-color:#0d6efd;box-shadow:0 0 0 3px rgba(13,110,253,.12);background:#fff;outline:0}
.field-icon{position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:#6c757d}
.switch{display:flex;align-items:center;gap:.75rem;background:#f8f9ff;border:1px solid #eef1f6;border-radius:12px;padding:.75rem 1rem}
</style>
<div class="container py-3" dir="rtl">
    <div class="emp-form-hero p-4 mb-4 position-relative">
        <div class="position-relative" style="z-index:1">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:56px;height:56px;border-radius:14px;background:rgba(255,255,255,.18);border:2px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center"><i class="fas fa-user-edit fa-lg"></i></div>
                    <div><h2 class="mb-1 fw-bold">تعديل: <?= htmlspecialchars($employee->full_name ?? $employee->emp_code) ?></h2><p class="mb-0 opacity-75 small">حدّث البيانات ثم احفظ التغييرات</p></div>
                </div>
                <a href="<?= APP_URL ?>/employees" class="btn" style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:999px;backdrop-filter:blur(6px)"><i class="fas fa-arrow-right ms-1"></i> العودة</a>
            </div>
        </div>
    </div>

    <form action="<?= APP_URL ?>/employees/update" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$employee->id ?>">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="form-card h-100">
                    <div class="card-head"><i class="fas fa-id-card text-primary"></i> المعلومات الأساسية</div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">يجب إدخال الرقم الوظيفي أو الاسم الكامل على الأقل.</p>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label small fw-bold">الرقم الوظيفي</label><div class="form-floating-custom"><i class="fas fa-hashtag field-icon"></i><input type="text" id="emp_code" name="emp_code" value="<?= htmlspecialchars($employee->emp_code ?? '') ?>"></div></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">الاسم الكامل *</label><div class="form-floating-custom"><i class="fas fa-user field-icon"></i><input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($employee->full_name ?? '') ?>"></div></div>
                            <div class="col-12"><label class="form-label small fw-bold">الصفة أو الكنية (اختياري)</label><div class="form-floating-custom"><i class="fas fa-tag field-icon"></i><input type="text" id="nickname" name="nickname" value="<?= htmlspecialchars($employee->nickname ?? '') ?>"></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="form-card h-100">
                    <div class="card-head"><i class="fas fa-briefcase text-success"></i> المعلومات الوظيفية</div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">يجب اختيار الفرع أو القسم على الأقل.</p>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label small fw-bold">الفرع</label><div class="form-floating-custom"><i class="fas fa-code-branch field-icon"></i><select id="branch_id" name="branch_id"><option value="">-- اختر الفرع --</option><?php foreach($branches as $b): ?><option value="<?= (int)$b->id ?>" <?= (string)$employee->branch_id===(string)$b->id?'selected':'' ?>><?= htmlspecialchars($b->name) ?></option><?php endforeach; ?></select></div></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">القسم</label><div class="form-floating-custom"><i class="fas fa-sitemap field-icon"></i><select id="department_id" name="department_id"><option value="">-- اختر القسم --</option><?php foreach($departments as $d): ?><option value="<?= (int)$d->id ?>" <?= (string)$employee->department_id===(string)$d->id?'selected':'' ?>><?= htmlspecialchars($d->name) ?></option><?php endforeach; ?></select></div></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">المنصب الوظيفي *</label><div class="form-floating-custom"><i class="fas fa-user-tie field-icon"></i><select id="position_id" name="position_id" required><option value="">-- اختر المنصب --</option><?php foreach($positions as $p): ?><option value="<?= (int)$p->id ?>" <?= (string)$employee->position_id===(string)$p->id?'selected':'' ?>><?= htmlspecialchars($p->title) ?></option><?php endforeach; ?></select></div></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">المدير المباشر</label><div class="form-floating-custom"><i class="fas fa-user-friends field-icon"></i><select id="manager_id" name="manager_id"><option value="">-- لا يوجد --</option><?php foreach($managers as $m): if($m->id==$employee->id) continue; ?><option value="<?= (int)$m->id ?>" <?= (string)$employee->manager_id===(string)$m->id?'selected':'' ?>><?= htmlspecialchars($m->full_name) ?></option><?php endforeach; ?></select></div></div>
                            <div class="col-12"><div class="switch"><input type="checkbox" id="is_active" name="is_active" value="1" <?= $employee->is_active?'checked':'' ?> style="width:42px;height:22px"><label for="is_active" class="fw-bold mb-0">الموظف نشط</label><span class="small text-muted ms-auto">إلغاء التفعيل يخفي الموظف من التقييمات</span></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-end mt-4">
            <a href="<?= APP_URL ?>/employees" class="btn btn-light border px-4" style="border-radius:999px">إلغاء</a>
            <button type="submit" class="btn btn-primary px-4" style="border-radius:999px"><i class="fas fa-save ms-1"></i> حفظ التعديلات</button>
        </div>
    </form>
</div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
