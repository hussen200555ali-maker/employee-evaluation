<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Backup;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Database;

class BackupController extends Controller {

    private $backupModel;
    private $db;

    public function __construct() {
        $this->backupModel = new Backup();
        $this->db = Database::getInstance();
    }

    /**
     * عرض صفحة إدارة النسخ الاحتياطي
     */
    public function index() {
        Auth::can('backup.manage');
        
        $logs = $this->backupModel->getBackupLogs();
        $lastBackup = $this->backupModel->getLastBackup();
        $stats = $this->backupModel->getBackupStats();
        
        $this->view('backup/index', [
            'pageTitle' => 'إدارة النسخ الاحتياطي',
            'logs' => $logs,
            'lastBackup' => $lastBackup,
            'stats' => $stats
        ]);
    }

    /**
     * عرض لوحة التحكم الاحترافية
     */
  /*  public function proDashboard() {
        Auth::can('backup.manage');
        
        $logs = $this->backupModel->getBackupLogs(20);
        $lastBackup = $this->backupModel->getLastBackup();
        $stats = $this->backupModel->getBackupStats();
        
        // الحصول على حالة الصحة من نظام المراقبة
        $monitor = new \App\Core\BackupMonitor();
        $health = $monitor->performHealthCheck();
        
        $this->view('backup/pro_dashboard', [
            'pageTitle' => 'النسخ الاحتياطي الاحترافي',
            'logs' => $logs,
            'lastBackup' => $lastBackup,
            'stats' => $stats,
            'health' => $health
        ]);
    }*/
        public function proDashboard() {
    Auth::can('backup.manage');
    
    $logs = $this->backupModel->getBackupLogs(20);
    $lastBackup = $this->backupModel->getLastBackup();
    $stats = $this->backupModel->getBackupStats();
    
    // الحصول على حالة الصحة من نظام المراقبة
    $monitor = new \App\Core\BackupMonitor();
    $health = $monitor->performHealthCheck();
    
    $this->view('backup/pro_dashboard', [
        'pageTitle' => 'النسخ الاحتياطي الاحترافي',
        'recentBackups' => $logs, // تم التصحيح هنا
        'lastBackup' => $lastBackup,
        'stats' => $stats,
        'health' => $health
    ]);
}

    /**
     * إنشاء نسخة احتياطية كاملة
     */
    public function createFullBackup() {
        Auth::can('backup.manage');
        
        $result = $this->backupModel->createFullBackup(Auth::id());
        
        if ($result['success']) {
            $message = "✅ تم إنشاء النسخة الاحتياطية الكاملة بنجاح";
            if ($result['encrypted']) {
                $message .= " (مشفرة)";
            }
            if ($result['compressed']) {
                $message .= " (مضغوطة - توفير " . $result['compression_ratio'] . "%)";
            }
            Flash::set($message, 'success');
        } else {
            Flash::set('❌ فشل في إنشاء النسخة الاحتياطية: ' . $result['error'], 'danger');
        }
        
        $this->redirect('/backup');
    }

    /**
     * إنشاء نسخة احتياطية تزايدية
     */
    public function createIncrementalBackup() {
        Auth::can('backup.manage');
        
        $result = $this->backupModel->createIncrementalBackup(Auth::id());
        
        if ($result['success']) {
            $message = "✅ تم إنشاء النسخة الاحتياطية التزايدية بنجاح";
            if ($result['encrypted']) {
                $message .= " (مشفرة)";
            }
            if ($result['compressed']) {
                $message .= " (مضغوطة - توفير " . $result['compression_ratio'] . "%)";
            }
            Flash::set($message, 'success');
        } else {
            Flash::set('❌ فشل في إنشاء النسخة الاحتياطية: ' . $result['error'], 'danger');
        }
        
        $this->redirect('/backup');
    }

    /**
     * إنشاء نسخة احتياطية ذكية (تزايدية حقيقية)
     */
    public function createSmartBackup() {
        Auth::can('backup.manage');
        
        $result = $this->backupModel->createSmartIncrementalBackup(Auth::id());
        
        if ($result['success']) {
            Flash::set("✅ تم إنشاء النسخة التزايدية الذكية بنجاح ({$result['modified_tables']} جدول)", 'success');
        } else {
            Flash::set('❌ فشل في إنشاء النسخة التزايدية الذكية: ' . $result['error'], 'danger');
        }
        
        $this->redirect('/backup');
    }

    /**
     * إنشاء نسخة احتياطية محسنة (لـ AJAX)
     */
    public function createOptimizedBackup() {
        Auth::can('backup.manage');
        
        // استخدام النسخ المحسن (هنا نستخدم النسخ الكامل المحسن)
        $result = $this->backupModel->createFullBackup(Auth::id());
        
        if ($result['success']) {
            $memoryUsage = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
            Flash::set("✅ تم إنشاء النسخة الاحتياطية بنجاح (الذاكرة: {$memoryUsage}MB)", 'success');
        } else {
            Flash::set('❌ فشل في إنشاء النسخة الاحتياطية: ' . $result['error'], 'danger');
        }
        
        $this->redirect('/backup');
    }

    /**
     * التحقق من سلامة النسخ
     */
    public function verifyIntegrity() {
        Auth::can('backup.manage');
        
        $manager = new \App\Core\BackupManager();
        $monitor = $manager->getMonitor();
        
        $results = $monitor->verifyBackupIntegrity();
        
        if ($results['success_rate'] == 100) {
            Flash::set('✅ جميع النسخ سليمة ومؤكدة', 'success');
        } else {
            Flash::set('⚠️ بعض النسخ تحتاج فحص: ' . $results['verified'] . '/' . $results['total'] . ' سليمة', 'warning');
        }
        
        $this->redirect('/backup');
    }

    /**
     * فحص صحة النظام
     */
    public function healthCheck() {
        Auth::can('backup.manage');
        
        $manager = new \App\Core\BackupManager();
        $monitor = $manager->getMonitor();
        
        $health = $monitor->performHealthCheck();
        
        // عرض النتائج في رسالة
        $messages = [];
        $messages[] = "📊 فحص صحة نظام النسخ الاحتياطي:";
        
        if ($health['disk_space']['status'] === 'healthy') {
            $messages[] = "✅ مساحة التخزين: جيدة (" . $health['disk_space']['free_gb'] . " GB متاحة)";
        } else if ($health['disk_space']['status'] === 'warning') {
            $messages[] = "⚠️ مساحة التخزين: منخفضة (" . $health['disk_space']['free_gb'] . " GB متاحة)";
        } else {
            $messages[] = "❌ مساحة التخزين: حرجة (" . $health['disk_space']['free_gb'] . " GB متاحة)";
        }
        
        if ($health['error_rate'] < 5) {
            $messages[] = "✅ معدل الأخطاء: مقبول (" . $health['error_rate'] . "%)";
        } else if ($health['error_rate'] < 20) {
            $messages[] = "⚠️ معدل الأخطاء: مرتفع (" . $health['error_rate'] . "%)";
        } else {
            $messages[] = "❌ معدل الأخطاء: خطير (" . $health['error_rate'] . "%)";
        }
        
        if ($health['backup_integrity']['success_rate'] > 95) {
            $messages[] = "✅ سلامة النسخ: ممتازة (" . $health['backup_integrity']['success_rate'] . "%)";
        } else {
            $messages[] = "⚠️ سلامة النسخ: تحتاج انتباه (" . $health['backup_integrity']['success_rate'] . "%)";
        }
        
        Flash::set(implode("<br>", $messages), 'info');
        $this->redirect('/backup');
    }

    /**
     * تحميل نسخة احتياطية - النسخة المحسنة
     */
  /*  public function downloadBackup($id) {
        Auth::can('backup.manage');
        
        $backup = $this->backupModel->getBackupById($id);
        
        $validation = $this->validateBackupFile($backup);
        if (!$validation['valid']) {
            Flash::set($validation['message'], 'danger');
            $this->redirect('/backup');
        }

        $filePath = $validation['filePath'];

        // إذا كانت النسخة مشفرة، نطلب كلمة المرور
        if ($backup->is_encrypted) {
            $this->handleEncryptedDownload($backup, $filePath);
            return;
        }

        // تحميل عادي
        $this->sendFileForDownload($filePath, $backup->backup_name);
    }

    */

public function downloadBackup($id) {
    Auth::can('backup.manage');
    
    $backup = $this->backupModel->getBackupById($id);
    
    if (!$backup) {
        Flash::set('النسخة الاحتياطية غير موجودة', 'danger');
        $this->redirect('/backup');
    }
    
    // التحقق من وجود الملف
    $validation = $this->validateBackupFile($backup);
    if (!$validation['valid']) {
        Flash::set($validation['message'], 'danger');
        $this->redirect('/backup');
    }

    $filePath = $validation['filePath'];

    // إذا كانت النسخة مشفرة، نطلب كلمة المرور
    if ($backup->is_encrypted) {
        // استخدام POST request بدلاً من GET
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['encryption_key'])) {
            $this->handleEncryptedDownload($backup, $filePath);
            return;
        } else {
            // عرض نموذج إدخال كلمة المرور
            $this->view('backup/encryption_prompt', [
                'pageTitle' => 'إدخال مفتاح التشفير',
                'backup' => $backup,
                'action' => 'download' // تحديد الإجراء
            ]);
            return;
        }
    }

    // تحميل عادي
    $this->sendFileForDownload($filePath, $backup->backup_name);
}

    /**
     * معالجة التحميل المشفر
     */
    private function handleEncryptedDownload($backup, $filePath) {
        if ($_POST && isset($_POST['encryption_key'])) {
            $encryption = new \App\Core\BackupEncryption();
            
            try {
                $decryptedData = $encryption->decryptBackup($filePath, $_POST['encryption_key']);
                
                // تنظيف المخزن المؤقت
                while (ob_get_level()) {
                    ob_end_clean();
                }

                // إرسال البيانات المفقرة
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . str_replace('.enc', '', basename($filePath)) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . strlen($decryptedData));
                
                echo $decryptedData;
                exit;
                
            } catch (\Exception $e) {
                Flash::set('❌ فشل فك التشفير: ' . $e->getMessage(), 'danger');
                $this->redirect('/backup');
            }
        } else {
            // عرض نموذج إدخال كلمة المرور
            $this->view('backup/encryption_prompt', [
                'pageTitle' => 'إدخال مفتاح التشفير',
                'backup' => $backup
            ]);
            return;
        }
    }

    /**
     * إرسال الملف للتحميل
     */
    private function sendFileForDownload($filePath, $backupName) {
        // تنظيف المخزن المؤقت
        while (ob_get_level()) {
            ob_end_clean();
        }

        // إعداد headers للتحميل
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        
        // إرسال الملف
        readfile($filePath);
        exit;
    }

    /**
     * استعادة نسخة احتياطية
     */
  /*  public function restoreBackup($id) {
        Auth::can('backup.manage');
        
        $backup = $this->backupModel->getBackupById($id);
        
        if (!$backup || $backup->status !== 'success' || !file_exists($backup->file_path)) {
            Flash::set('النسخة الاحتياطية غير موجودة', 'danger');
            $this->redirect('/backup');
        }
        
        // إذا كانت النسخة مشفرة، نطلب كلمة المرور
        if ($backup->is_encrypted) {
            $this->handleEncryptedRestore($backup);
            return;
        }
        
        // استخدام دالة الاستعادة من النموذج مباشرة
        $result = $this->backupModel->restoreBackup($backup->file_path);
        
        if ($result['success']) {
            Flash::set('✅ تم استعادة النسخة الاحتياطية بنجاح: ' . $result['message'], 'success');
        } else {
            Flash::set('❌ فشل في استعادة النسخة الاحتياطية: ' . $result['error'], 'danger');
        }
        
        $this->redirect('/backup');
    }
*/

// إنشاء دالة منفصلة للاستعادة
public function restoreBackup($id) {
    Auth::can('backup.manage');
    
    $backup = $this->backupModel->getBackupById($id);
    
    if (!$backup) {
        Flash::set('النسخة الاحتياطية غير موجودة', 'danger');
        $this->redirect('/backup');
    }
    
    $validation = $this->validateBackupFile($backup);
    if (!$validation['valid']) {
        Flash::set($validation['message'], 'danger');
        $this->redirect('/backup');
    }

    $filePath = $validation['filePath'];

    if ($backup->is_encrypted) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['encryption_key'])) {
            $this->handleEncryptedRestore($backup, $filePath);
            return;
        } else {
            $this->view('backup/encryption_prompt_restore', [
                'pageTitle' => 'إدخال مفتاح التشفير للاستعادة',
                'backup' => $backup,
                'action' => 'restore' // تحديد الإجراء
            ]);
            return;
        }
    }
    
    // الاستعادة العادية
    $result = $this->backupModel->restoreBackup($filePath);
    
    if ($result['success']) {
        Flash::set('✅ تم استعادة النسخة الاحتياطية بنجاح', 'success');
    } else {
        Flash::set('❌ فشل في استعادة النسخة الاحتياطية: ' . $result['error'], 'danger');
    }
    
    $this->redirect('/backup');
}
    /**
     * معالجة الاستعادة المشفرة
     */
    private function handleEncryptedRestore($backup) {
        if ($_POST && isset($_POST['encryption_key'])) {
            $result = $this->backupModel->restoreEncryptedBackup($backup->file_path, $_POST['encryption_key']);
            
            if ($result['success']) {
                Flash::set('✅ تم استعادة النسخة الاحتياطية المشفرة بنجاح: ' . $result['message'], 'success');
            } else {
                Flash::set('❌ فشل في استعادة النسخة الاحتياطية: ' . $result['error'], 'danger');
            }
            
            $this->redirect('/backup');
        } else {
            // عرض نموذج إدخال كلمة المرور
            $this->view('backup/encryption_prompt_restore', [
                'pageTitle' => 'إدخال مفتاح التشفير للاستعادة',
                'backup' => $backup
            ]);
            return;
        }
    }

    /**
     * حذف نسخة احتياطية
     */
    public function deleteBackup($id) {
        Auth::can('backup.manage');
        
        $backup = $this->backupModel->getBackupById($id);
        
        if (!$backup) {
            Flash::set('النسخة الاحتياطية غير موجودة', 'danger');
            $this->redirect('/backup');
        }
        
        // حذف الملف إذا كان موجوداً
        if ($backup->file_path && file_exists($backup->file_path)) {
            unlink($backup->file_path);
        }
        
        // حذف السجل من قاعدة البيانات
        $result = $this->backupModel->deleteBackupById($id);
        
        if ($result) {
            Flash::set('✅ تم حذف النسخة الاحتياطية بنجاح', 'success');
        } else {
            Flash::set('❌ فشل في حذف النسخة الاحتياطية', 'danger');
        }
        
        $this->redirect('/backup');
    }

    /**
     * تنظيف النسخ القديمة
     */
    public function cleanOldBackups() {
        Auth::can('backup.manage');
        
        $result = $this->backupModel->cleanOldBackups();
        
        if ($result['deleted_files'] > 0) {
            Flash::set('✅ تم تنظيف ' . $result['deleted_files'] . ' نسخة قديمة', 'success');
        } else {
            Flash::set('ℹ️ لا توجد نسخ قديمة تحتاج تنظيف', 'info');
        }
        
        $this->redirect('/backup');
    }

    /**
     * التحقق من صحة ملف النسخ الاحتياطي
     */
    private function validateBackupFile($backup) {
        if (!$backup || $backup->status !== 'success') {
            return ['valid' => false, 'message' => 'النسخة الاحتياطية غير صالحة'];
        }

        $filePath = $backup->file_path;
        
        // محاولة تصحيح المسار إذا لزم الأمر
        if (!file_exists($filePath)) {
            // محاولة البحث في مجلد backups
            $filename = basename($filePath);
            $alternativePath = APP_ROOT . '/backups/' . $filename;
            
            if (file_exists($alternativePath)) {
                $filePath = $alternativePath;
            } else {
                return ['valid' => false, 'message' => 'ملف النسخة الاحتياطية غير موجود'];
            }
        }

        // التحقق من حجم الملف
        $fileSize = filesize($filePath);
        if ($fileSize === 0 || $fileSize === false) {
            return ['valid' => false, 'message' => 'ملف النسخة الاحتياطية فارغ أو تالف'];
        }

        return ['valid' => true, 'filePath' => $filePath];
    }

    /**
     * إدارة التشفير
     */
    public function encryptionManager() {
        Auth::can('backup.manage');
        
        $this->view('backup/encryption_manager', [
            'pageTitle' => 'إدارة تشفير النسخ الاحتياطي'
        ]);
    }

    /**
     * توليد مفتاح تشفير جديد
     */
    public function generateEncryptionKey() {
        Auth::can('backup.manage');
        
        $manager = new \App\Core\BackupManager();
        $encryption = $manager->getEncryption();
        
        $key = $encryption->generateKey();
        
        Flash::set('🔐 تم توليد مفتاح تشفير جديد: ' . $key, 'success');
        $this->redirect('/backup/encryption');
    }

    /**
     * الحصول على تقدم النسخ (لـ AJAX)
     */
    public function getBackupProgress($logId) {
        Auth::can('backup.manage');
        
        $progress = $this->backupModel->getCurrentProgress($logId);
        
        header('Content-Type: application/json');
        echo json_encode(['progress' => $progress]);
        exit;
    }

    /**
     * API للحصول على إحصائيات النسخ
     */
    public function getBackupStatsApi() {
        Auth::can('backup.manage');
        
        $stats = $this->backupModel->getBackupStats();
        $health = (new \App\Core\BackupMonitor())->performHealthCheck();
        
        header('Content-Type: application/json');
        echo json_encode([
            'stats' => $stats,
            'health' => $health
        ]);
        exit;
    }

    /**
     * إجراء سريع (لـ AJAX)
     */
    public function quickAction() {
        Auth::can('backup.manage');
        
        $action = $_POST['action'] ?? '';
        $backupId = $_POST['backup_id'] ?? null;
        
        switch ($action) {
            case 'verify_single':
                if ($backupId) {
                    $result = $this->backupModel->verifyBackupIntegrity($backupId);
                    echo json_encode($result);
                }
                break;
                
            case 'get_progress':
                if ($backupId) {
                    $progress = $this->backupModel->getCurrentProgress($backupId);
                    echo json_encode(['progress' => $progress]);
                }
                break;
                
            default:
                echo json_encode(['error' => 'إجراء غير معروف']);
        }
        exit;
    }
    // في BackupController أو ملف helper منفصل
public function getComponentName($component) {
    $names = [
        'disk_space' => 'مساحة التخزين',
        'error_rate' => 'معدل الأخطاء',
        'backup_integrity' => 'سلامة النسخ',
        'last_backup' => 'آخر نسخة'
    ];
    return $names[$component] ?? $component;
}

public function getStatusColor($status) {
    $colors = [
        'healthy' => 'success',
        'warning' => 'warning',
        'critical' => 'danger',
        'unknown' => 'secondary'
    ];
    return $colors[$status] ?? 'secondary';
}

public function getStatusText($status) {
    $texts = [
        'healthy' => 'سليم',
        'warning' => 'تحذير',
        'critical' => 'حرج',
        'unknown' => 'غير معروف'
    ];
    return $texts[$status] ?? 'غير معروف';
}

protected function formatBytes($bytes, $precision = 2) {
    if ($bytes <= 0) return '0 B';
    
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
// في BackupController - أضف دوال جديدة
public function encryptionKeys() {
    Auth::can('backup.manage');
    
    $this->db->query("SELECT bek.*, bl.backup_name 
                     FROM backup_encryption_keys bek 
                     JOIN backup_logs bl ON bek.backup_id = bl.id 
                     ORDER BY bek.created_at DESC");
    $keys = $this->db->fetchAll();
    
    $this->view('backup/encryption_keys', [
        'pageTitle' => 'إدارة مفاتيح التشفير',
        'keys' => $keys
    ]);
}

public function deleteEncryptionKey($id) {
    Auth::can('backup.manage');
    
    $this->db->query("DELETE FROM backup_encryption_keys WHERE id = ?");
    $this->db->bind(1, $id);
    $result = $this->db->execute();
    
    if ($result) {
        Flash::set('✅ تم حذف مفتاح التشفير بنجاح', 'success');
    } else {
        Flash::set('❌ فشل في حذف مفتاح التشفير', 'danger');
    }
    
    $this->redirect('/backup/encryption/keys');
}
//صفحة تقارير الصحة
public function healthReports() {
    Auth::can('backup.manage');
    
    $this->db->query("SELECT * FROM backup_health_logs ORDER BY checked_at DESC LIMIT 50");
    $healthLogs = $this->db->fetchAll();
    
    $this->view('backup/health_reports', [
        'pageTitle' => 'تقارير صحة النظام',
        'healthLogs' => $healthLogs
    ]);
}
 /**
     * التحقق من حالة الجدولة التلقائية
     */
    public function schedulerStatus() {
        Auth::can('backup.manage');
        
        try {
            $projectPath = "C:\\xamppp\\htdocs\\employee-evaluation";
            $powerShellPath = "powershell.exe";
            $scriptPath = $projectPath . "\\backup.ps1";
            
            // التحقق من وجود السكريبت
            if (!file_exists($scriptPath)) {
                throw new \Exception("سكريبت النسخ غير موجود: " . $scriptPath);
            }
            
            // تنفيذ أمر التحقق من حالة الجدولة
            $command = "& \"$scriptPath\" status";
            $output = shell_exec("$powerShellPath -Command \"$command\" 2>&1");
            
            // تحليل الناتج للحصول على الحالة
            $status = [
                'status' => 'unknown',
                'message' => 'لم يتم تحديد الحالة',
                'state' => null,
                'nextRun' => null
            ];
            
            if (strpos($output, 'المهمة المجدولة نشطة') !== false) {
                $status['status'] = 'active';
                $status['message'] = 'المهمة المجدولة نشطة وتعمل تلقائياً';
                
                // استخراج الحالة والموعد القادم
                if (preg_match('/الحالة:\s*(\w+)/', $output, $matches)) {
                    $status['state'] = $matches[1];
                }
                if (preg_match('/الموعد القادم:\s*(.+)/', $output, $matches)) {
                    $status['nextRun'] = trim($matches[1]);
                }
            } elseif (strpos($output, 'لم يتم إعداد الجدولة') !== false) {
                $status['status'] = 'inactive';
                $status['message'] = 'لم يتم إعداد الجدولة التلقائية بعد';
            } elseif (strpos($output, 'خطأ في التحقق') !== false) {
                $status['status'] = 'error';
                $status['message'] = 'خطأ في التحقق من حالة الجدولة';
            }
            
            // إضافة الناتج الكامل للت debugging
            $status['raw_output'] = $output;
            
            header('Content-Type: application/json');
            echo json_encode($status);
            
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'خطأ في التحقق من الحالة: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * إعداد الجدولة التلقائية
     */
    public function setupScheduler() {
        Auth::can('backup.manage');
        
        try {
            $projectPath = "C:\\xamppp\\htdocs\\employee-evaluation";
            $powerShellPath = "powershell.exe";
            $scriptPath = $projectPath . "\\backup.ps1";
            
            // التحقق من وجود السكريبت
            if (!file_exists($scriptPath)) {
                throw new \Exception("سكريبت النسخ غير موجود: " . $scriptPath);
            }
            
            // تنفيذ أمر إعداد الجدولة
            $command = "& \"$scriptPath\" setup";
            $output = shell_exec("$powerShellPath -Command \"$command\" 2>&1");
            
            $success = (strpos($output, 'اكتمل الإعداد بنجاح') !== false) || 
                      (strpos($output, 'تم إعداد الجدولة التلقائية بنجاح') !== false);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'message' => $success ? 
                    'تم إعداد الجدولة التلقائية بنجاح' : 
                    'فشل في إعداد الجدولة التلقائية',
                'output' => $output
            ]);
            
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'خطأ في إعداد الجدولة: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * اختبار التشغيل التلقائي
     */
    public function testScheduler() {
        Auth::can('backup.manage');
        
        try {
            $projectPath = "C:\\xamppp\\htdocs\\employee-evaluation";
            $powerShellPath = "powershell.exe";
            $scriptPath = $projectPath . "\\backup.ps1";
            
            // التحقق من وجود السكريبت
            if (!file_exists($scriptPath)) {
                throw new \Exception("سكريبت النسخ غير موجود: " . $scriptPath);
            }
            
            // تنفيذ نسخ احتياطي تجريبي
            $command = "& \"$scriptPath\" test";
            $output = shell_exec("$powerShellPath -Command \"$command\" 2>&1");
            
            $success = (strpos($output, 'اكتملت عملية النسخ الاحتياطي بنجاح') !== false);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'message' => $success ? 
                    'تم التشغيل التجريبي بنجاح' : 
                    'فشل في التشغيل التجريبي',
                'output' => $output
            ]);
            
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'خطأ في التشغيل التجريبي: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * إزالة الجدولة التلقائية
     */
    public function removeScheduler() {
        Auth::can('backup.manage');
        
        try {
            $projectPath = "C:\\xamppp\\htdocs\\employee-evaluation";
            $powerShellPath = "powershell.exe";
            $scriptPath = $projectPath . "\\backup.ps1";
            
            // التحقق من وجود السكريبت
            if (!file_exists($scriptPath)) {
                throw new \Exception("سكريبت النسخ غير موجود: " . $scriptPath);
            }
            
            // تنفيذ أمر إزالة الجدولة
            $command = "& \"$scriptPath\" remove";
            $output = shell_exec("$powerShellPath -Command \"$command\" 2>&1");
            
            $success = (strpos($output, 'تم إزالة المهمة المجدولة') !== false);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'message' => $success ? 
                    'تم إزالة الجدولة التلقائية بنجاح' : 
                    'فشل في إزالة الجدولة التلقائية',
                'output' => $output
            ]);
            
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'خطأ في إزالة الجدولة: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * فحص مساحة التخزين
     */
    public function checkDisk() {
        Auth::can('backup.manage');
        
        try {
            $projectPath = "C:\\xamppp\\htdocs\\employee-evaluation";
            $powerShellPath = "powershell.exe";
            $scriptPath = $projectPath . "\\backup.ps1";
            
            // استخدام نظام المراقبة الموجود
            $monitor = new \App\Core\BackupMonitor();
            $diskSpace = $monitor->checkDiskSpace();
            
            $message = "مساحة التخزين: " . $diskSpace['free_gb'] . " GB متاحة من " . $diskSpace['total_gb'] . " GB";
            
            if ($diskSpace['status'] === 'healthy') {
                $message = "✅ " . $message . " (جيدة)";
            } elseif ($diskSpace['status'] === 'warning') {
                $message = "⚠️ " . $message . " (منخفضة)";
            } else {
                $message = "❌ " . $message . " (حرجة)";
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => $message,
                'details' => $diskSpace
            ]);
            
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'خطأ في فحص المساحة: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * تنظيف النسخ القديمة
     */
    public function cleanBackups() {
        Auth::can('backup.manage');
        
        try {
            $projectPath = "C:\\xamppp\\htdocs\\employee-evaluation";
            $powerShellPath = "powershell.exe";
            $scriptPath = $projectPath . "\\backup.ps1";
            
            // التحقق من وجود السكريبت
            if (!file_exists($scriptPath)) {
                throw new \Exception("سكريبت النسخ غير موجود: " . $scriptPath);
            }
            
            // تنفيذ أمر التنظيف
            $command = "& \"$scriptPath\" clean";
            $output = shell_exec("$powerShellPath -Command \"$command\" 2>&1");
            
            $success = (strpos($output, 'اكتمل تنظيف النسخ القديمة') !== false);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'message' => $success ? 
                    'تم تنظيف النسخ القديمة بنجاح' : 
                    'فشل في تنظيف النسخ القديمة',
                'output' => $output
            ]);
            
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'خطأ في التنظيف: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * حفظ إعدادات النسخ الاحتياطي
     */
    public function saveSettings() {
        Auth::can('backup.manage');
        
        try {
            $settingsModel = new \App\Models\BackupSetting();
            
            // جمع البيانات من النموذج
            $data = [
                'backup_type' => $_POST['backup_type'] ?? 'full',
                'schedule_type' => $_POST['schedule_type'] ?? 'manual',
                'schedule_time' => $_POST['schedule_time'] ?? '02:00',
                'schedule_day' => $_POST['schedule_day'] ?? null,
                'retention_days' => intval($_POST['retention_days'] ?? 30),
                'encryption_enabled' => isset($_POST['encryption_enabled']) ? 1 : 0,
                'compression_enabled' => isset($_POST['compression_enabled']) ? 1 : 1,
                'auto_cleanup' => isset($_POST['auto_cleanup']) ? 1 : 1
            ];
            
            // حساب وقت النسخ التالي
            $scheduler = new \App\Core\BackupScheduler();
            $nextBackupTime = $scheduler->calculateNextBackupTime((object)$data);
            
            // حفظ الإعدادات
            $result = $settingsModel->saveSettings($data, Auth::id(), $nextBackupTime);
            
            if ($result) {
                Flash::set('✅ تم حفظ الإعدادات بنجاح', 'success');
                
                // إذا كانت الجدولة تلقائية، نقوم بإعداد المهمة المجدولة
                if ($data['schedule_type'] !== 'manual') {
                    // سيتم تنفيذ الإعداد عبر AJAX من الواجهة
                    Flash::set('⚠️ يرجى تفعيل التشغيل التلقائي يدوياً باستخدام الزر المخصص', 'info');
                }
            } else {
                Flash::set('❌ فشل في حفظ الإعدادات', 'danger');
            }
            
        } catch (\Exception $e) {
            Flash::set('❌ خطأ في حفظ الإعدادات: ' . $e->getMessage(), 'danger');
        }
        
        $this->redirect('/backup/settings');
    }

    /**
     * عرض صفحة الإعدادات
     */
    public function settings() {
        Auth::can('backup.manage');
        
        $settingsModel = new \App\Models\BackupSetting();
        $settings = $settingsModel->getCurrentSettings();
        
        $this->view('backup/settings', [
            'pageTitle' => 'إعدادات النسخ الاحتياطي',
            'settings' => $settings
        ]);
    }

}
?>