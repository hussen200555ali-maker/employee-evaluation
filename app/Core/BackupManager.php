<?php
namespace App\Core;

class BackupManager {
    private $encryption;
    private $scheduler;
    private $monitor;
    private $backupModel;
    
    public function __construct() {
        $this->encryption = new BackupEncryption();
        $this->scheduler = new BackupScheduler();
        $this->monitor = new BackupMonitor();
        $this->backupModel = new \App\Models\Backup();
    }
    
    /**
     * إنشاء نسخة احتياطية محسنة
     */
    public function createBackup($type = 'auto', $userId = null) {
        try {
            // التحقق من المساحة
            $diskSpace = $this->monitor->checkDiskSpace();
            if ($diskSpace['status'] === 'critical') {
                throw new \Exception("مساحة التخزين غير كافية: " . $diskSpace['free_gb'] . " GB متاحة فقط");
            }
            
            // تحديد النوع تلقائياً إذا لزم الأمر
            if ($type === 'auto') {
                $type = $this->determineOptimalBackupType();
            }
            
            // تنفيذ النسخ
            if ($type === 'full') {
                $result = $this->backupModel->createFullBackup($userId);
            } else {
                $result = $this->backupModel->createIncrementalBackup($userId);
            }
            
            // تسجيل النتيجة
            $this->monitor->logBackupResult($result);
            
            // تنظيف النسخ القديمة إذا كانت ناجحة
            if ($result['success']) {
                $this->cleanOldBackups();
            }
            
            return $result;
            
        } catch (\Exception $e) {
            $this->monitor->logError($e->getMessage());
            throw $e;
        }
    }
    
    /**
     * تحديد نوع النسخ الأمثل
     */
    private function determineOptimalBackupType() {
        $dataSize = $this->monitor->getDatabaseSize();
        $lastBackupInfo = $this->monitor->checkLastBackup();
        $lastFullBackupTime = $lastBackupInfo['days_ago'] !== null ? 
            strtotime("-" . $lastBackupInfo['days_ago'] . " days") : 0;
        
        // إذا مر أكثر من أسبوع على آخر نسخ كامل أو لا توجد نسخ
        if ($lastBackupInfo['days_ago'] === null || $lastBackupInfo['days_ago'] > 7) {
            return 'full';
        }
        
        // إذا كان حجم البيانات كبيراً، استخدم التزايدي
        if ($dataSize > 2 * 1024 * 1024 * 1024) { // 2GB
            return 'incremental';
        }
        
        return 'full';
    }
    
    /**
     * حساب الوقت الأمثل
     */
    public function calculateOptimalTime($settings) {
        // استخدام الجدولة الحالية مؤقتاً
        $scheduler = new BackupScheduler();
        return $scheduler->calculateNextBackupTime((object)$settings);
    }
    
    /**
     * تنظيف النسخ القديمة
     */
    private function cleanOldBackups() {
        try {
            $this->backupModel->cleanOldBackups();
        } catch (\Exception $e) {
            $this->monitor->logError("Failed to clean old backups: " . $e->getMessage());
        }
    }

    /**
     * الحصول على حالة المراقبة (للاستخدام الخارجي)
     */
    public function getMonitor() {
        return $this->monitor;
    }

    /**
     * الحصول على نموذج النسخ (للاستخدام الخارجي)
     */
    public function getBackupModel() {
        return $this->backupModel;
    }

    /**
     * الحصول على مشفر النسخ (للاستخدام الخارجي)
     */
    public function getEncryption() {
        return $this->encryption;
    }
}
?>