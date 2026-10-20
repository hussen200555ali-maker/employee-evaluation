<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\BackupSetting;
use App\Core\Auth;
use App\Core\Flash;

class BackupSettingController extends Controller {

    private $backupSettingModel;

    public function __construct() {
        $this->backupSettingModel = new BackupSetting();
    }

    /**
     * عرض صفحة إعدادات النسخ الاحتياطي المحسنة
     */
    public function index() {
        Auth::enforce('backup.manage');
        
        $settings = $this->backupSettingModel->getCurrentSettings();
        $settingsHistory = $this->backupSettingModel->getSettingsHistory(5);
        
        $this->view('backup/pro_settings', [
            'pageTitle' => 'الإعدادات الاحترافية للنسخ الاحتياطي',
            'settings' => $settings,
            'settingsHistory' => $settingsHistory
        ]);
    }

    /**
     * حفظ الإعدادات مع التفعيل التلقائي - النسخة الاحترافية
     */
    public function save() {
        Auth::enforce('backup.manage');
        
        if ($_POST) {
            // جمع البيانات من النموذج مع القيم الافتراضية
            $data = [
                'backup_type' => $_POST['backup_type'] ?? 'full',
                'schedule_type' => $_POST['schedule_type'] ?? 'manual',
                'schedule_time' => $_POST['schedule_time'] ?? '02:00',
                'schedule_day' => $_POST['schedule_day'] ?? null,
                'retention_days' => intval($_POST['retention_days'] ?? 30),
                'encryption_enabled' => isset($_POST['encryption_enabled']) ? 1 : 0,
                'compression_enabled' => isset($_POST['compression_enabled']) ? 1 : 0
            ];

            // حساب وقت النسخ التالي تلقائياً إذا لم تكن الجدولة يدوية
            $nextBackupTime = null;
            if ($data['schedule_type'] != 'manual') {
                $nextBackupTime = $this->calculateOptimalBackupTime($data);
            }

            // حفظ الإعدادات في قاعدة البيانات
            $result = $this->backupSettingModel->saveSettings($data, Auth::id(), $nextBackupTime);
            
            // إعداد رسالة للمستخدم بناءً على نتيجة الحفظ
            if ($result) {
                $message = $this->generateSuccessMessage($data, $nextBackupTime);
                Flash::set($message, 'success');
                
                // تسجيل النشاط
                $this->logActivity('تم تحديث إعدادات النسخ الاحتياطي');
            } else {
                Flash::set('❌ فشل في حفظ الإعدادات', 'danger');
            }
        }
        
        $this->redirect('/backup/settings');
    }

    /**
     * حساب الوقت الأمثل للنسخ
     */
    private function calculateOptimalBackupTime($settings) {
        $manager = new \App\Core\BackupManager();
        return $manager->calculateOptimalTime($settings);
    }

    /**
     * توليد رسالة النجاح
     */
    private function generateSuccessMessage($data, $nextBackupTime) {
        $messages = [];
        
        $messages[] = "✅ تم حفظ الإعدادات بنجاح!";
        
        if ($data['schedule_type'] != 'manual') {
            $scheduleDesc = $this->getScheduleDescription($data);
            $messages[] = "🕒 النظام سيقوم تلقائياً بعمل نسخ احتياطي " . $scheduleDesc;
            
            if ($nextBackupTime) {
                $messages[] = "📅 النسخ التالي: " . date('Y-m-d H:i', strtotime($nextBackupTime));
            }
        } else {
            $messages[] = "👆 يمكنك عمل نسخ يدوي من صفحة النسخ الاحتياطي";
        }
        
        // إضافة معلومات الأمان
        if ($data['encryption_enabled']) {
            $messages[] = "🔐 التشفير: مفعل - النسخ مشفرة وآمنة";
        }
        
        if ($data['compression_enabled']) {
            $messages[] = "🗜️ الضغط: مفعل - توفير في المساحة";
        }
        
        $messages[] = "💾 مدة الاحتفاظ: " . $data['retention_days'] . " يوماً";
        
        return implode("<br>", $messages);
    }

    /**
     * وصف الجدولة للمستخدم بطريقة واضحة
     */
    private function getScheduleDescription($settings) {
        switch ($settings['schedule_type']) {
            case 'daily':
                return "📅 يومياً الساعة " . $settings['schedule_time'];
            case 'weekly':
                $arabicDays = [
                    'sunday' => 'الأحد',
                    'monday' => 'الإثنين',
                    'tuesday' => 'الثلاثاء',
                    'wednesday' => 'الأربعاء',
                    'thursday' => 'الخميس',
                    'friday' => 'الجمعة',
                    'saturday' => 'السبت'
                ];
                $dayName = $arabicDays[$settings['schedule_day']] ?? $settings['schedule_day'];
                return "📅 أسبوعياً كل " . $dayName . " الساعة " . $settings['schedule_time'];
            case 'monthly':
                return "📅 شهرياً يوم " . $settings['schedule_day'] . " الساعة " . $settings['schedule_time'];
            default:
                return "";
        }
    }

    /**
     * تسجيل النشاط
     */
    private function logActivity($action) {
        // هنا يمكن إضافة تسجيل النشاط في سجل النظام
        error_log("[" . date('Y-m-d H:i:s') . "] 👤 User " . Auth::id() . ": " . $action);
    }
    
}
?>