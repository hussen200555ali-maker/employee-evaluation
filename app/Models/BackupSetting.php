<?php
namespace App\Models;

use App\Core\Model;

class BackupSetting extends Model {
    
    /**
     * الحصول على الإعدادات الحالية مع التحسينات
     */  /**
     * الحصول على الإعدادات الحالية مع التحسينات
     */
    public function getCurrentSettings() {
        $this->db->query("SELECT * FROM backup_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
        $settings = $this->db->fetch();
        
        // إذا لم توجد إعدادات، نرجع إعدادات افتراضية
        if (!$settings) {
            return $this->getDefaultSettings();
        }
        
        return $settings;
    }
    /**
     * الإعدادات الافتراضية
     */
      private function getDefaultSettings() {
        return (object)[
            'id' => null,
            'backup_type' => 'full',
            'schedule_type' => 'manual',
            'schedule_time' => '02:00:00',
            'schedule_day' => null,
            'retention_days' => 30,
            'is_active' => 0,
            'encryption_enabled' => 0,
            'compression_enabled' => 1,
            'last_backup' => null,
            'next_backup' => null,
            'created_by' => null,
            'created_at' => null,
            'updated_at' => null
        ];
    }

    /**
     * حفظ الإعدادات مع وقت النسخ التالي
     */
  /**
     * حفظ الإعدادات مع وقت النسخ التالي
     */
    public function saveSettings($data, $userId = null, $nextBackupTime = null) {
        // تعطيل جميع الإعدادات السابقة
        $this->db->query("UPDATE backup_settings SET is_active = 0");
        $this->db->execute();
        
        // إنشاء إعدادات جديدة
        return $this->createSettings($data, $userId, $nextBackupTime);
    }


    /**
     * إنشاء إعدادات جديدة محسنة
     */
     private function createSettings($data, $userId, $nextBackupTime) {
        $this->db->query(
            "INSERT INTO backup_settings 
            (backup_type, schedule_type, schedule_time, schedule_day, retention_days, 
             encryption_enabled, compression_enabled, is_active, next_backup, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)"
        );
        
        $this->db->bind(1, $data['backup_type'] ?? 'full');
        $this->db->bind(2, $data['schedule_type'] ?? 'manual');
        $this->db->bind(3, $data['schedule_time'] ?? '02:00');
        $this->db->bind(4, $data['schedule_day'] ?? null);
        $this->db->bind(5, $data['retention_days'] ?? 30);
        $this->db->bind(6, $data['encryption_enabled'] ?? 0);
        $this->db->bind(7, $data['compression_enabled'] ?? 1);
        $this->db->bind(8, $nextBackupTime);
        $this->db->bind(9, $userId);
        
        return $this->db->execute();
    }

    /**
     * تحديث وقت النسخ التالي
     */
  public function updateNextBackupTime($id, $nextBackupTime) {
        $this->db->query(
            "UPDATE backup_settings SET next_backup = ?, updated_at = NOW() WHERE id = ?"
        );
        $this->db->bind(1, $nextBackupTime);
        $this->db->bind(2, $id);
        return $this->db->execute();
    }

    /**
     * تحديث آخر نسخ احتياطي
     */
   public function updateLastBackupTime($id, $lastBackupTime) {
        $this->db->query(
            "UPDATE backup_settings SET last_backup = ?, updated_at = NOW() WHERE id = ?"
        );
        $this->db->bind(1, $lastBackupTime);
        $this->db->bind(2, $id);
        return $this->db->execute();
    }

    /**
     * تفعيل الإعدادات
     */
  public function activateSettings($id) {
        $this->db->query(
            "UPDATE backup_settings SET is_active = 1, updated_at = NOW() WHERE id = ?"
        );
        $this->db->bind(1, $id);
        return $this->db->execute();
    }

    /**
     * الحصول على الإعدادات المجدولة
     */
  public function getScheduledBackups() {
        $this->db->query(
            "SELECT * FROM backup_settings 
            WHERE is_active = 1 AND schedule_type != 'manual' 
            ORDER BY next_backup ASC"
        );
        return $this->db->fetchAll();
    }

    /**
     * الحصول على تاريخ الإعدادات
     */
/**
     * الحصول على تاريخ الإعدادات - الإصلاح
     */
  public function getSettingsHistory($limit = 10) {
        $this->db->query(
            "SELECT bs.*, u.username 
             FROM backup_settings bs 
             LEFT JOIN users u ON bs.created_by = u.id 
             ORDER BY bs.created_at DESC 
             LIMIT ?"
        );
        $this->db->bind(1, $limit);
        return $this->db->fetchAll();
    }
     /**
     * تشغيل جميع المهام المجدولة (للاستخدام في cron)
     */
    public function runAllScheduledTasks() {
        $this->logSchedulerEvent('بدء جميع المهام المجدولة');
        $this->checkScheduledBackups();
        $this->logSchedulerEvent('انتهاء جميع المهام المجدولة');
    }

    /**
     * تسجيل أحداث الجدولة
     */
    private function logSchedulerEvent($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] 🕒 Scheduler: {$message}";
        error_log($logMessage);
        echo $logMessage . "\n";
    }
}
?>