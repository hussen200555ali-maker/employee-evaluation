<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap');
:root{--brand:#0d5e52;--deep:#0c2823;--gold:#c8943f;--canvas:#f0f4f8;--card:#fff;--border:#e2e8f0;--muted:#64748b;--text:#1e293b;--shadow-sm:0 2px 8px rgba(15,42,68,.06);--shadow-md:0 8px 24px rgba(15,42,68,.10);}

.form-hero{background:linear-gradient(135deg,#0c2823 0%,#0d5e52 55%,#115e4b 100%);border-radius:20px;padding:clamp(24px,3vw,36px);color:#fff;position:relative;overflow:hidden;margin-bottom:28px;box-shadow:0 16px 48px rgba(12,40,35,.25);border:1px solid rgba(255,255,255,.08);}
.form-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 80% 20%,rgba(200,148,63,.12) 0%,transparent 40%),radial-gradient(circle at 20% 80%,rgba(255,255,255,.04) 0%,transparent 40%);pointer-events:none;}
.form-hero::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(200,148,63,.4),transparent);}
.hero-row{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;position:relative;z-index:1;}
.hero-title{display:flex;align-items:center;gap:14px;}
.hero-icon{width:56px;height:56px;border-radius:14px;background:rgba(255,255,255,.12);border:2px solid rgba(200,148,63,.3);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:22px;flex-shrink:0;box-shadow:0 4px 16px rgba(200,148,63,.2);}
.hero-text h2{margin:0;font-size:clamp(24px,3vw,32px);font-weight:900;color:#fff;}
.hero-text p{margin:4px 0 0;font-size:clamp(12px,1.1vw,14px);color:rgba(255,255,255,.65);}
.btn-back{display:inline-flex;align-items:center;gap:8px;padding:10px 22px;border-radius:12px;font-size:14px;font-weight:700;text-decoration:none;background:rgba(255,255,255,.12);color:#fff;border:2px solid rgba(255,255,255,.25);backdrop-filter:blur(4px);transition:all .3s cubic-bezier(.4,0,.2,1);}
.btn-back:hover{background:var(--gold);color:#fff;border-color:var(--gold);transform:translateY(-2px);box-shadow:0 6px 20px rgba(200,148,63,.35);}

.form-card{background:var(--card);border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow-sm);overflow:hidden;}
.card-head{padding:16px 20px;border-bottom:1px solid var(--border);background:#f8fafc;display:flex;align-items:center;gap:8px;font-weight:800;font-size:16px;color:var(--text);}
.card-body{padding:clamp(20px,2.5vw,28px);}

.field-group{position:relative;}
.field-group label{display:block;font-size:13px;font-weight:700;color:var(--muted);margin-bottom:6px;}
.field-group input,.field-group select{width:100%;padding:10px 14px 10px 38px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;color:var(--text);background:#fff;transition:border-color .2s,box-shadow .2s;outline:none;font-family:'Tajawal',sans-serif;}
.field-group input:focus,.field-group select:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(13,94,82,.1);}
.field-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:14px;pointer-events:none;}



.eval-section{display:none;}

@media(max-width:700px){.hero-row{flex-direction:column;align-items:flex-start;}}
</style>

    <!-- Hero Header -->
    <div class="form-hero">
        <div class="hero-row">
            <div class="hero-title">
                <div class="hero-icon"><i class="fas fa-user-plus"></i></div>
                <div class="hero-text">
                    <h2>إضافة موظف جديد</h2>
                    <p>أدخل البيانات الأساسية والوظيفية — الحقول المميزة بـ * مطلوبة</p>
                </div>
            </div>
            <a href="<?= APP_URL ?>/employees" class="btn-back"><i class="fas fa-arrow-right"></i> العودة للقائمة</a>
        </div>
    </div>

    <form action="<?= APP_URL ?>/employees" method="POST" id="add-employee-form">
        <?= csrf_field() ?>
        <div class="row g-4">
            <!-- المعلومات الأساسية -->
            <div class="col-lg-6">
                <div class="form-card h-100">
                    <div class="card-head"><i class="fas fa-id-card" style="color:var(--brand)"></i> المعلومات الأساسية</div>
                    <div class="card-body">
                        <p style="font-size:13px;color:var(--muted);margin-bottom:20px;">يجب إدخال الرقم الوظيفي أو الاسم الكامل على الأقل.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="field-group">
                                    <label>الرقم الوظيفي</label>
                                    <i class="fas fa-hashtag field-icon"></i>
                                    <input type="text" id="emp_code" name="emp_code" placeholder="مثال: EMP-001">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-group">
                                    <label>الاسم الكامل *</label>
                                    <i class="fas fa-user field-icon"></i>
                                    <input type="text" id="full_name" name="full_name" placeholder="الاسم الرباعي" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="field-group">
                                    <label>الصفة أو الكنية (اختياري)</label>
                                    <i class="fas fa-tag field-icon"></i>
                                    <input type="text" id="nickname" name="nickname" placeholder="مثال: أبو أحمد">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- المعلومات الوظيفية -->
            <div class="col-lg-6">
                <div class="form-card h-100">
                    <div class="card-head"><i class="fas fa-briefcase" style="color:var(--gold)"></i> المعلومات الوظيفية</div>
                    <div class="card-body">
                        <p style="font-size:13px;color:var(--muted);margin-bottom:20px;">يجب اختيار الفرع أو القسم على الأقل.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="field-group">
                                    <label>الفرع</label>
                                    <i class="fas fa-code-branch field-icon"></i>
                                    <select id="branch_id" name="branch_id">
                                        <option value="">-- اختر الفرع --</option>
                                        <?php foreach($branches as $b): ?>
                                            <option value="<?= (int)$b->id ?>"><?= htmlspecialchars($b->name) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-group">
                                    <label>القسم</label>
                                    <i class="fas fa-sitemap field-icon"></i>
                                    <select id="department_id" name="department_id">
                                        <option value="">-- اختر القسم --</option>
                                        <?php foreach($departments as $d): ?>
                                            <option value="<?= (int)$d->id ?>"><?= htmlspecialchars($d->name) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-group">
                                    <label>المنصب الوظيفي *</label>
                                    <i class="fas fa-user-tie field-icon"></i>
                                    <select id="position_id" name="position_id" required>
                                        <option value="">-- اختر المنصب --</option>
                                        <?php foreach($positions as $p): ?>
                                            <option value="<?= (int)$p->id ?>"><?= htmlspecialchars($p->title) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-group">
                                    <label>المدير المباشر</label>
                                    <i class="fas fa-user-friends field-icon"></i>
                                    <select id="manager_id" name="manager_id">
                                        <option value="">-- لا يوجد --</option>
                                        <?php foreach($managers as $m): ?>
                                            <option value="<?= (int)$m->id ?>"><?= htmlspecialchars($m->full_name) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- قسم التقييم (مخفى) -->
        <div id="evaluation-section" class="form-card mt-4 eval-section">
            <div class="card-head"><i class="fas fa-tasks" style="color:var(--gold)"></i> بدء تقييم فوري</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="field-group">
                            <label>القائم بالتقييم</label>
                            <i class="fas fa-search field-icon"></i>
                            <input type="text" id="evaluator_employee_search" placeholder="ابحث بالاسم أو الرقم..." autocomplete="off">
                            <input type="hidden" name="evaluator_employee_id" id="evaluator_employee_id">
                            <div id="evaluator-search-results" class="search-results"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="field-group">
                            <label>تاريخ التقييم</label>
                            <i class="fas fa-calendar field-icon"></i>
                            <input type="date" id="evaluation_date" name="evaluation_date" value="<?= date('Y-m-d') ?>">
                        </div>
                        <small id="hijri-date-display-employee" class="d-block mt-1" style="color:var(--brand);font-weight:700;font-size:12px;"><?php $hijri_today = convertToHijri(date('Y-m-d')); echo $hijri_today ? 'الموافق: '.$hijri_today->full_date : '' ?></small>
                    </div>
                    <div class="col-md-3">
                        <div class="field-group">
                            <label>قالب التقييم</label>
                            <i class="fas fa-clipboard-list field-icon"></i>
                            <select id="template_id" name="template_id" disabled>
                                <option value="">اختر المنصب أولاً...</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- زر مخفي لإرسال حفظ+تقييم عبر JS -->
        <button type="submit" id="submit-and-evaluate" name="action" value="save_and_evaluate" style="display:none"></button>

        <!-- أزرار الإجراءات -->
        <div class="d-flex flex-wrap gap-2 justify-content-center mt-4">
            <a href="<?= APP_URL ?>/employees" class="btn btn-outline-secondary px-4" style="border-radius:12px;font-weight:700;font-family:'Tajawal',sans-serif">
                <i class="fas fa-times"></i> إلغاء
            </a>
            <button type="submit" name="action" value="save" class="btn btn-success px-4" style="border-radius:12px;font-weight:700;font-family:'Tajawal',sans-serif">
                <i class="fas fa-save"></i> حفظ الموظف
            </button>
            <button type="button" id="btn-save-and-evaluate" class="btn btn-warning px-4" style="border-radius:12px;font-weight:700;font-family:'Tajawal',sans-serif">
                <i class="fas fa-tasks"></i> حفظ وبدء التقييم
            </button>
        </div>
    </form>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const inp=document.getElementById('evaluation_date'), out=document.getElementById('hijri-date-display-employee');
    if(inp&&out) inp.addEventListener('change', function(){
        const d=this.value; if(!d){out.textContent='';return;} out.textContent='جارِ التحويل...';
        fetch(`<?= APP_URL ?>/api/to-hijri?date=${d}`).then(r=>r.json()).then(j=>{ out.textContent=j.hijri?'الموافق: '+j.hijri:'خطأ في التحويل'; }).catch(()=> out.textContent='فشل الاتصال');
    });
});
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
