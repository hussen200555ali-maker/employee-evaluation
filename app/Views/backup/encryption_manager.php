<?php
$this->view('layouts/header', ['pageTitle' => $pageTitle]);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">🔐 إدارة تشفير النسخ الاحتياطي</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6>معلومات التشفير الحالية</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>حالة التشفير:</strong> 
                                        <?php 
                                        $settings = (new App\Models\BackupSetting())->getCurrentSettings();
                                        echo $settings && $settings->encryption_enabled ? 
                                            '<span class="badge bg-success">مفعل</span>' : 
                                            '<span class="badge bg-secondary">غير مفعل</span>';
                                        ?>
                                    </p>
                                    <p><strong>الخوارزمية:</strong> AES-256-GCM</p>
                                    <p><strong>مستوى الأمان:</strong> عالي</p>
                                    <p><strong>حجم المفتاح:</strong> 256-bit</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6>الإجراءات السريعة</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="<?php echo APP_URL; ?>/backup/encryption/generate-key" 
                                           class="btn btn-primary">
                                           🗝️ توليد مفتاح تشفير جديد
                                        </a>
                                        <a href="<?php echo APP_URL; ?>/backup/settings" 
                                           class="btn btn-outline-secondary">
                                           ⚙️ إعدادات التشفير
                                        </a>
                                        <button class="btn btn-info" onclick="showEncryptionHelp()">
                                            ❓ مساعدة التشفير
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6>🔒 نصائح الأمان</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>✅ افعل:</h6>
                                            <ul>
                                                <li>احفظ مفاتيح التشفير في مكان آمن</li>
                                                <li>استخدم مدير كلمات مرور موثوق</li>
                                                <li>قم بعمل نسخة احتياطية من مفاتيح التشفير</li>
                                                <li>غيّر المفاتيح بشكل دوري</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>❌ لا تفعل:</h6>
                                            <ul>
                                                <li>لا تشارك مفاتيح التشفير مع أي شخص</li>
                                                <li>لا تحفظ المفاتيح في ملفات نصية عادية</li>
                                                <li>لا ترسل المفاتيح عبر البريد الإلكتروني</li>
                                                <li>لا تستخدم كلمات مرور ضعيفة</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6>📊 إحصائيات التشفير</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $backupModel = new \App\Models\Backup();
                                    $stats = $backupModel->getBackupStats();
                                    ?>
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <h4><?php echo $stats->encrypted_backups ?? 0; ?></h4>
                                            <small>نسخ مشفرة</small>
                                        </div>
                                        <div class="col-md-3">
                                            <h4><?php echo $stats->total_backups ?? 0; ?></h4>
                                            <small>إجمالي النسخ</small>
                                        </div>
                                        <div class="col-md-3">
                                            <h4>256-bit</h4>
                                            <small>قوة التشفير</small>
                                        </div>
                                        <div class="col-md-3">
                                            <h4>AES-GCM</h4>
                                            <small>الخوارزمية</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- نموذج المساعدة -->
<div class="modal fade" id="encryptionHelpModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">❓ مساعدة نظام التشفير</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>كيف يعمل التشفير:</h6>
                <p>يستخدم النظام خوارزمية AES-256-GCM للتشفير، وهي نفس الخوارزمية المستخدمة في الأنظمة المالية والعسكرية.</p>
                
                <h6>مميزات النظام:</h6>
                <ul>
                    <li>🔒 تشفير قوي 256-bit</li>
                    <li>🛡️ حماية من العبث (Authentication)</li>
                    <li>⚡ أداء عالي مع الأمان</li>
                    <li>📁 دعم جميع أحجام الملفات</li>
                </ul>
                
                <h6>نصائح الاستخدام:</h6>
                <ol>
                    <li>قم بتوليد مفتاح جديد لكل نسخة مهمة</li>
                    <li>احفظ المفتاح في مكان آمن ومنفصل</li>
                    <li>اختبر استعادة النسخ المشفرة بشكل دوري</li>
                    <li>استخدم مدير كلمات مرور مثل LastPass أو 1Password</li>
                </ol>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<script>
function showEncryptionHelp() {
    var modal = new bootstrap.Modal(document.getElementById('encryptionHelpModal'));
    modal.show();
}
</script>

<?php
$this->view('layouts/footer');
?>