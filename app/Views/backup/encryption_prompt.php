<?php
$this->view('layouts/header', ['pageTitle' => $pageTitle]);
?>
<section class="app-hero" aria-labelledby="bk-enc" style="margin-bottom:1rem">
    <div class="app-hero__left">
        <span class="app-eyebrow"><i class="fas fa-lock"></i> التشفير</span>
        <h1 id="bk-enc">إدخال <span class="accent">مفتاح التشفير</span></h1>
        <p>هذه النسخة مشفرة بمفتاح خاص — أدخل المفتاح لفكها ومتابعة التحميل أو الاستعادة.</p>
    </div>
</section>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">🔐 إدخال مفتاح التشفير</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>معلومات النسخة:</h6>
                        <p><strong>الاسم:</strong> <?php echo htmlspecialchars($backup->backup_name); ?></p>
                        <p><strong>التاريخ:</strong> <?php echo date('Y-m-d H:i', strtotime($backup->created_at)); ?></p>
                        <p><strong>الحجم:</strong> <?php echo round($backup->file_size / 1024 / 1024, 2); ?> MB</p>
                    </div>
                    
                    <p class="text-muted">هذه النسخة مشفرة. يرجى إدخال مفتاح التشفير لتحميلها.</p>
                    
                    <form method="POST">
                <?= csrf_field() ?>
                         <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <div class="mb-3">
                            <label for="encryption_key" class="form-label">مفتاح التشفير</label>
                            <input type="password" class="form-control" id="encryption_key" 
                                   name="encryption_key" required placeholder="أدخل مفتاح التشفير...">
                            <div class="form-text">أدخل المفتاح الذي تم استخدامه لتشفير هذه النسخة.</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-unlock"></i> ظپظƒ التشفير والتحميل
                            </button>
                            <a href="<?php echo APP_URL; ?>/backup" class="btn btn-secondary">
                                <i class="fas fa-arrow-right"></i> رجوع إلى قائمة النسخ
                            </a>
                        </div>
                    </form>
                    
                    <div class="mt-3">
                        <div class="alert alert-warning">
                            <small>
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>ملاحظة:</strong> إذا فقدت مفتاح التشفير، لن تتمكن من فتح هذه النسخة.
                                تأكد من حفظ المفاتيح ظپظٹ مكان آمن.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$this->view('layouts/footer');
?>
