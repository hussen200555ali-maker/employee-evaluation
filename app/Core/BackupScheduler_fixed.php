<?php
namespace App\Core;

use App\Models\Backup;
use App\Models\BackupSetting;

class BackupScheduler {

    private $backupModel;
    private $settingModel;
    private $monitor;

    public function __construct() {
        $this->backupModel = new Backup();
        $this->settingModel = new BackupSetting();
        $this->monitor = new BackupMonitor();
    }

    /**
     * التحقق من النسخ المجدولة مع تحسينات الاحترافية
     */
    public function checkScheduledBackups() {
        try {
            // فحص صحة النظام أولاً
            $health = $this->monitor->performHealthCheck();

            if ($health['disk_space']['status'] === 'critical') {
                error_log("[" . date('Y-m-d H:i:s') . "] ❌ إيقاف النسخ - مساحة تخزين حرجة");
                return;
            }

            // الحصول على الإعدادات الحالية
            $settings = $this->settingModel->getCurrentSettings();

            if (!$settings || $settings->schedule_type === 'manual' || !$settings->is_active) {
                return;
            }

            $currentTime = date('Y-m-d H:i:s');
            $currentTimestamp = time();

            // إذا لم يكن هناك وقت للنسخ التالي، نحسبه
            if (!$settings->next_backup) {
                $nextBackupTime = $this->calculateOptimalBackupTime($settings);
                $this->settingModel->updateNextBackupTime($settings->id, $nextBackupTime);
                return;
            }

            // التحقق إذا حان وقت النسخ
            if ($currentTime >= $settings->next_backup) {
                error_log("[" . date('Y-m-d H:i:s') . "] 🚀 وقت النسخ المجدول - البدء في التنفيذ");
                $this->executeScheduledBackup($settings);
            }

        } catch (\Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] 💥 Backup Scheduler Error: " . $e->getMessage());
            $this->monitor->logError($e->getMessage());
        }
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
        // إرسال الرسالة إلى stdout بدلاً من error_log لتجنب التعامل معها كخطأ
        echo $logMessage . "\n";
    }

    /**
     * حساب الوقت الأمثل للنسخ بناءً على تحليل النظام
     */
    private function calculateOptimalBackupTime($settings) {
        $systemAnalysis = $this->analyzeSystemUsage();

        // إذا كان النظام يعاني من ضغط، نؤجل النسخ
        if ($systemAnalysis['load'] > 0.8) {
            $optimalTime = $this->findLowUsageTime();
        } else {
            $optimalTime = $this->calculateNextBackupTime($settings);
        }

        return $optimalTime;
    }

    /**
     * تحليل استخدام النظام
     */
    private function analyzeSystemUsage() {
        // محاكاة تحليل استخدام النظام
        $load = function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 0.1;
        $memoryUsage = memory_get_usage(true) / (memory_get_peak_usage(true) ?: 1);

        return [
            'load' => $load,
            'memory_usage' => $memoryUsage,
            'status' => $load > 0.7 ? 'high' : ($load > 0.4 ? 'medium' : 'low')
        ];
    }

    /**
     * إيجاد وقت الاستخدام المنخفض
     */
    private function findLowUsageTime() {
        $currentHour = (int)date('H');

        // افتراضياً، أوقات الليل هي الأقل استخداماً
        if ($currentHour >= 6 && $currentHour < 18) {
            // إذا كان وقت النهار، نؤجل حتى الليل
            return date('Y-m-d 02:00:00', strtotime('+1 day'));
        } else {
            // إذا كان الليل، ننسخ بعد ساعة
            return date('Y-m-d H:i:s', strtotime('+1 hour'));
        }
    }

    /**
     * تنفيذ النسخ المجدول مع معالجة محسنة
     */
    private function executeScheduledBackup($settings) {
        $backupType = $settings->backup_type;

        error_log("[" . date('Y-m-d H:i:s') . "] 🚀 Starting automated " . $backupType . " backup");

        try {
            // تنفيذ النسخ بناءً على النوع
            if ($backupType === 'full') {
                $result = $this->backupModel->createFullBackup(1); // النظام = user_id 1
            } else {
                $result = $this->backupModel->createIncrementalBackup(1);
            }

            // تحديث وقت آخر نسخ احتياطي
            $this->settingModel->updateLastBackupTime($settings->id, date('Y-m-d H:i:s'));

            // حساب وقت النسخ التالي مع التحسينات
            $nextBackupTime = $this->calculateOptimalBackupTime($settings);
            $this->settingModel->updateNextBackupTime($settings->id, $nextBackupTime);

            // تسجيل النتيجة المحسنة
            if ($result['success']) {
                error_log("[" . date('Y-m-d H:i:s') . "] ✅ Automated backup completed successfully");

                // إرسال إشعار بنجاح النسخ
                $this->sendBackupNotification($result, 'success');
            } else {
                error_log("[" . date('Y-m-d H:i:s') . "] ❌ Automated backup failed: " . $result['error']);

                // إرسال إشعار بفشل النسخ
                $this->sendBackupNotification($result, 'error');
            }

        } catch (\Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] 💥 Automated backup exception: " . $e->getMessage());
            $this->sendBackupNotification(['error' => $e->getMessage()], 'error');
        }
    }

    /**
     * إرسال إشعارات النسخ
     */
    private function sendBackupNotification($result, $type) {
        // يمكن تطوير هذا ليرسل إشعارات حقيقية (بريد، Telegram، إلخ)
        $message = $type === 'success' 
            ? "✅ تم إنشاء نسخة احتياطية تلقائية بنجاح\n📁 الملف: " . ($result['filename'] ?? 'N/A')
            : "❌ فشل النسخ الاحتياطي التلقائي: " . ($result['error'] ?? 'Unknown error');

        error_log("[" . date('Y-m-d H:i:s') . "] 📢 Backup Notification: " . $message);

        // هنا يمكن إضافة إرسال إشعارات فعلية
        // $this->sendEmailNotification($message);
        // $this->sendTelegramNotification($message);
    }

    /**
     * حساب وقت النسخ التالي - نسخة محسنة
     */
    public function calculateNextBackupTime($settings) {
        $now = time();

        switch ($settings->schedule_type) {
            case 'daily':
                $scheduledTimeToday = strtotime(date('Y-m-d') . ' ' . $settings->schedule_time);

                if ($scheduledTimeToday <= $now) {
                    $scheduledTimeToday = strtotime('+1 day', $scheduledTimeToday);
                }
                return date('Y-m-d H:i:s', $scheduledTimeToday);

            case 'weekly':
                $dayName = $this->convertToEnglishDay($settings->schedule_day);
                $nextDay = strtotime("next " . $dayName . " " . $settings->schedule_time);

                if ($nextDay <= $now) {
                    $nextDay = strtotime("+1 week", $nextDay);
                }
                return date('Y-m-d H:i:s', $nextDay);

            case 'monthly':
                $dayNumber = intval($settings->schedule_day);
                $nextDate = $this->getNextMonthDate($dayNumber, $settings->schedule_time);
                $nextDateTime = strtotime($nextDate);

                if ($nextDateTime <= $now) {
                    $nextDate = $this->getNextMonthDate($dayNumber, $settings->schedule_time, true);
                }
                return $nextDate;

            default:
                return null;
        }
    }

    /**
     * تحويل اليوم العربي إلى إنجليزي
     */
    private function convertToEnglishDay($arabicDay) {
        $days = [
            'sunday' => 'sunday',
            'monday' => 'monday', 
            'tuesday' => 'tuesday',
            'wednesday' => 'wednesday',
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            'الأحد' => 'sunday',
            'الإثنين' => 'monday',
            'الثلاثاء' => 'tuesday',
            'الأربعاء' => 'wednesday',
            'الخميس' => 'thursday',
            'الجمعة' => 'friday',
            'السبت' => 'saturday'
        ];
        return $days[strtolower($arabicDay)] ?? 'sunday';
    }

    /**
     * الحصول على التاريخ الشهري التالي
     */
    private function getNextMonthDate($dayNumber, $time, $forceNextMonth = false) {
        $currentDay = date('j');
        $currentMonth = date('n');
        $currentYear = date('Y');

        if ($forceNextMonth || $dayNumber < $currentDay) {
            $nextMonth = $currentMonth + 1;
            $nextYear = $currentYear;
            if ($nextMonth > 12) {
                $nextMonth = 1;
                $nextYear++;
            }

            $daysInNextMonth = date('t', mktime(0, 0, 0, $nextMonth, 1, $nextYear));
            $actualDay = min($dayNumber, $daysInNextMonth);

            $targetDate = date('Y-m-d', mktime(0, 0, 0, $nextMonth, $actualDay, $nextYear));
        } else {
            $daysInCurrentMonth = date('t');
            $actualDay = min($dayNumber, $daysInCurrentMonth);
            $targetDate = date('Y-m-d', mktime(0, 0, 0, $currentMonth, $actualDay, $currentYear));
        }

        return $targetDate . ' ' . $time;
    }

    /**
     * تشغيل الجدولة التلقائية - نسخة محسنة مع نافذة زمنية
     */
    public function runAutoScheduler() {
        try {
            $currentTime = date('H:i');
            $currentDay = date('Y-m-d');

            // الحصول على الإعدادات الحالية
            $settings = $this->settingModel->getCurrentSettings();

            if (!$settings || $settings->schedule_type === 'manual') {
                return ['status' => 'skipped', 'reason' => 'الجدولة يدوية'];
            }

            // التحقق من وقت التنفيذ مع نافذة زمنية (5 دقائق قبل وبعد)
            $scheduledTime = date('H:i', strtotime($settings->schedule_time));
            $scheduledTimestamp = strtotime($settings->schedule_time);
            $currentTimestamp = time();

            // حساب نافذة زمنية (5 دقائق قبل وبعد الوقت المحدد)
            $windowStart = date('H:i', $scheduledTimestamp - 300); // 5 دقائق قبل
            $windowEnd = date('H:i', $scheduledTimestamp + 300);   // 5 دقائق بعد

            // التحقق إذا كان الوقت الحالي ضمن النافذة الزمنية
            $isInTimeWindow = ($currentTime >= $windowStart && $currentTime <= $windowEnd);

            // التحقق أيضاً إذا كان وقت النسخ التالي قد حان
            $isNextBackupTime = false;
            if ($settings->next_backup) {
                $isNextBackupTime = (date('Y-m-d H:i:s') >= $settings->next_backup);
            }

            if ($isInTimeWindow || $isNextBackupTime) {
                // التحقق من نوع الجدولة
                $shouldRun = false;

                switch ($settings->schedule_type) {
                    case 'daily':
                        $shouldRun = true;
                        break;

                    case 'weekly':
                        $scheduledDay = $this->convertToEnglishDay($settings->schedule_day);
                        $currentEnglishDay = strtolower(date('l'));
                        $shouldRun = ($scheduledDay === $currentEnglishDay);
                        break;

                    case 'monthly':
                        $currentDayOfMonth = date('j');
                        $scheduledDay = intval($settings->schedule_day);
                        $shouldRun = ($currentDayOfMonth === $scheduledDay);
                        break;
                }

                if ($shouldRun) {
                    error_log("[" . date('Y-m-d H:i:s') . "] 🚀 تشغيل النسخ المجدول تلقائياً");
                    return $this->executeScheduledBackup($settings);
                }
            }

            return ['status' => 'waiting', 'next_check' => 'كل دقيقة'];

        } catch (\Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] 💥 خطأ في الجدولة التلقائية: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}