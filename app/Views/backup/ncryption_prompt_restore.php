<?php
$this->view('layouts/header', ['pageTitle' => $pageTitle]);
?>
<section class="app-hero" aria-labelledby="bk-rest" style="margin-bottom:1rem">
    <div class="app-hero__left">
        <span class="app-eyebrow"><i class="fas fa-lock"></i> الاستعادة</span>
        <h1 id="bk-rest">استعادة <span class="accent">نسخة مشفرة</span></h1>
        <p>أدخل مفتاح التشفير لفك النسخة — سيتحقق النظام منها ثم ينفذ الاستعادة.</p>
    </div>
</section>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">🔐 إدخال مفتاح التشفير للاستعادة</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>معلومات النسخة:</h6>
                        <p><strong>الاسم:</strong> <?php echo htmlspecialchars($backup->backup_name); ?></p>
                        <p><strong>التاريخ:</strong> <?php echo date('Y-m-d H:i', strtotime($backup->created_at)); ?></p>
                        <p><strong>النوع:</strong> <?php echo $backup->backup_type === 'full' ? 'كاملة' : 'تزايدية'; ?></p>
                    </div>
                    
                    <p class="text-muted">هذه النسخة مشفرة. يرجى إدخال مفتاح التشفير لاستعادتها.</p>
                    
                    <form method="POST">
                <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="encryption_key" class="form-label">مفتاح التشفير</label>
                            <input type="password" class="form-control" id="encryption_key" 
                                   name="encryption_key" required placeholder="أدخل مفتاح التشفير...">
                            <div class="form-text">أدخل المفتاح الذي تم استخدامه لتشفير هذه النسخة.</div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> تحذير مهم:</h6>
                            <ul>
                                <li>استعادة النسخة الاحتياطية ستحذف جميع البيانات الحالية</li>
                                <li>سيتم استبدال جميع الجداول الحالية</li>
                                <li>هذه العملية لا يمكن التراجع عنها</li>
                                <li>تأكد من وجود نسخة احتياطية حديثة قبل المتابعة</li>
                            </ul>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-undo"></i> ظپظƒ التشفير والاستعادة
                            </button>
                            <a href="<?php echo APP_URL; ?>/backup" class="btn btn-secondary">
                                <i class="fas fa-arrow-right"></i> رجوع إلى قائمة النسخ
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$this->view('layouts/footer');
?>
