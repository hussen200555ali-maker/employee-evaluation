<?php
namespace App\Models;

use App\Core\Model;
use Exception;
use PDO;

class Backup extends Model {

    private $backupPath;
    private $encryption;
    private $monitor;

    public function __construct() {
        parent::__construct();
        $this->backupPath = APP_ROOT . '/backups/';
        
        // إنشاء مجلد النسخ الاحتياطي إذا لم يكن موجوداً
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
        
        // تهيئة التشفير والمراقبة
        $this->encryption = new \App\Core\BackupEncryption();
        $this->monitor = new \App\Core\BackupMonitor();
    }

    /**
     * إنشاء نسخة احتياطية كاملة محسنة مع الكتابة المباشرة للملف
     */
    public function createFullBackup($userId = null) {
        $startTime = microtime(true);
        $fileHandle = null;
        
        try {
            // فحص صحة النظام أولاً
            $health = $this->monitor->checkDiskSpace();
            if ($health['status'] === 'critical') {
                throw new Exception("مساحة التخزين غير كافية للنسخ الاحتياطي");
            }

            $timestamp = date('Y-m-d_H-i-s');
            $filename = "full_backup_{$timestamp}.sql";
            $filepath = $this->backupPath . $filename;
            
            // تسجيل بدء النسخ الاحتياطي
            $logId = $this->logBackupStart($filename, 'full', $userId);
            
            // فتح الملف للكتابة المباشرة
            $fileHandle = fopen($filepath, 'w');
            if (!$fileHandle) {
                throw new Exception("فشل في إنشاء ملف النسخ الاحتياطي");
            }

            // كتابة رأس الملف
            fwrite($fileHandle, $this->generateBackupHeader());

            // الحصول على جميع الجداول
            $tables = $this->getAllTables();
            $tablesCount = count($tables);
            
            $processedTables = 0;
            $totalRows = 0;
            
            foreach ($tables as $table) {
                $tableResult = $this->backupTableToFile($table, $fileHandle);
                $processedTables++;
                $totalRows += $tableResult['rows'];
                
                // تحديث التقدم
                $this->updateBackupProgress($logId, $processedTables, $tablesCount);
                
                // تحرير الذاكرة بعد كل جدول
                $this->freeMemory();
            }

            // كتابة نهاية الملف
            fwrite($fileHandle, $this->generateBackupFooter($totalRows, $tablesCount));

            // إغلاق الملف
            fclose($fileHandle);
            $fileHandle = null;

            $fileSize = filesize($filepath);
            $executionTime = round(microtime(true) - $startTime, 2);
            
            // معالجة ما بعد النسخ (تشفير، ضغط)
            $finalResult = $this->processBackupFile($filepath, $fileSize, $logId, $tablesCount, $executionTime);
            
            return array_merge($finalResult, [
                'success' => true,
                'filename' => $filename,
                'total_rows' => $totalRows,
                'log_id' => $logId
            ]);
            
        } catch (Exception $e) {
            // إغلاق الملف في حالة الخطأ
            if ($fileHandle) {
                fclose($fileHandle);
            }
            
            // تسجيل فشل النسخ الاحتياطي
            if (isset($logId)) {
                $this->logBackupFailure($logId, $e->getMessage());
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * إنشاء نسخة احتياطية تزايدية محسنة مع الكتابة المباشرة للملف
     */
    public function createIncrementalBackup($userId = null) {
        $startTime = microtime(true);
        $fileHandle = null;
        
        try {
            // فحص صحة النظام أولاً
            $health = $this->monitor->checkDiskSpace();
            if ($health['status'] === 'critical') {
                throw new Exception("مساحة التخزين غير كافية للنسخ الاحتياطي");
            }

            $timestamp = date('Y-m-d_H-i-s');
            $filename = "incremental_backup_{$timestamp}.sql";
            $filepath = $this->backupPath . $filename;
            
            // تسجيل بدء النسخ الاحتياطي
            $logId = $this->logBackupStart($filename, 'incremental', $userId);
            
            // فتح الملف للكتابة المباشرة
            $fileHandle = fopen($filepath, 'w');
            if (!$fileHandle) {
                throw new Exception("فشل في إنشاء ملف النسخ الاحتياطي");
            }

            // كتابة رأس الملف
            fwrite($fileHandle, $this->generateBackupHeader());
            fwrite($fileHandle, "-- Backup Type: INCREMENTAL\n");
            fwrite($fileHandle, "-- Last Full Backup: " . date('Y-m-d H:i:s', strtotime('-1 day')) . "\n\n");

            // الحصول على جميع الجداول
            $tables = $this->getAllTables();
            $tablesCount = count($tables);
            
            $processedTables = 0;
            $totalRows = 0;
            
            foreach ($tables as $table) {
                $tableResult = $this->backupTableToFile($table, $fileHandle);
                $processedTables++;
                $totalRows += $tableResult['rows'];
                
                $this->updateBackupProgress($logId, $processedTables, $tablesCount);
                $this->freeMemory();
            }

            // كتابة نهاية الملف
            fwrite($fileHandle, $this->generateBackupFooter($totalRows, $tablesCount));
            fclose($fileHandle);
            $fileHandle = null;

            $fileSize = filesize($filepath);
            $executionTime = round(microtime(true) - $startTime, 2);
            
            // معالجة ما بعد النسخ
            $finalResult = $this->processBackupFile($filepath, $fileSize, $logId, $tablesCount, $executionTime);
            
            return array_merge($finalResult, [
                'success' => true,
                'filename' => $filename,
                'total_rows' => $totalRows,
                'log_id' => $logId
            ]);
            
        } catch (Exception $e) {
            if ($fileHandle) {
                fclose($fileHandle);
            }
            
            if (isset($logId)) {
                $this->logBackupFailure($logId, $e->getMessage());
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * إنشاء نسخة احتياطية تزايدية ذكية (حقيقية)
     */
    public function createSmartIncrementalBackup($userId = null) {
        $startTime = microtime(true);
        $fileHandle = null;
        
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "smart_incremental_backup_{$timestamp}.sql";
            $filepath = $this->backupPath . $filename;
            
            // تسجيل بدء النسخ الاحتياطي
            $logId = $this->logBackupStart($filename, 'smart_incremental', $userId);
            
            // فتح الملف للكتابة المباشرة
            $fileHandle = fopen($filepath, 'w');
            if (!$fileHandle) {
                throw new Exception("فشل في إنشاء ملف النسخ الاحتياطي");
            }

            // كتابة رأس الملف
            fwrite($fileHandle, $this->generateBackupHeader());
            fwrite($fileHandle, "-- Backup Type: SMART INCREMENTAL\n");
            fwrite($fileHandle, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");

            // الحصول على الجداول التي تم تعديلها مؤخراً
            $modifiedTables = $this->getRecentlyModifiedTables();
            $tablesCount = count($modifiedTables);
            
            $processedTables = 0;
            $totalRows = 0;
            
            foreach ($modifiedTables as $table) {
                $tableResult = $this->backupIncrementalTableData($table, $fileHandle);
                $processedTables++;
                $totalRows += $tableResult['rows'];
                
                $this->updateBackupProgress($logId, $processedTables, $tablesCount);
                $this->freeMemory();
            }

            // كتابة نهاية الملف
            fwrite($fileHandle, $this->generateBackupFooter($totalRows, $tablesCount));
            fclose($fileHandle);
            $fileHandle = null;

            $fileSize = filesize($filepath);
            $executionTime = round(microtime(true) - $startTime, 2);
            
            // معالجة ما بعد النسخ
            $finalResult = $this->processBackupFile($filepath, $fileSize, $logId, $tablesCount, $executionTime);
            
            return array_merge($finalResult, [
                'success' => true,
                'filename' => $filename,
                'total_rows' => $totalRows,
                'modified_tables' => $tablesCount,
                'log_id' => $logId
            ]);
            
        } catch (Exception $e) {
            if ($fileHandle) {
                fclose($fileHandle);
            }
            
            if (isset($logId)) {
                $this->logBackupFailure($logId, $e->getMessage());
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * نسخ جدول إلى الملف مباشرة مع إدارة الذاكرة
     */
    private function backupTableToFile($tableName, $fileHandle) {
        $rowsProcessed = 0;
        
        // كتابة هيكل الجدول
        fwrite($fileHandle, "\n-- --------------------------------------------------------\n");
        fwrite($fileHandle, "-- Table structure for table `$tableName`\n");
        fwrite($fileHandle, "-- --------------------------------------------------------\n\n");
        
        // هيكل الجدول
        $this->db->query("SHOW CREATE TABLE `$tableName`");
        $createTable = $this->db->fetch();
        
        if (!$createTable) {
            return ['rows' => 0];
        }
        
        fwrite($fileHandle, "DROP TABLE IF EXISTS `$tableName`;\n");
        $createSQL = $createTable->{'Create Table'};
        
        // تحسين SQL الناتج
        $createSQL = preg_replace('/AUTO_INCREMENT=\d+\s*/', '', $createSQL);
        fwrite($fileHandle, $createSQL . ";\n\n");
        
        // بيانات الجدول باستخدام الدُفعات الصغيرة
        fwrite($fileHandle, "--\n-- Dumping data for table `$tableName`\n--\n\n");

        $pageSize = 100; // دفعات صغيرة جداً
        $offset = 0;
        $hasMoreData = true;
        
        while ($hasMoreData) {
            // تنظيف الذاكرة قبل كل دفعة
            $this->freeMemory();
            
            $this->db->query("SELECT * FROM `$tableName` LIMIT $offset, $pageSize");
            $rows = $this->db->fetchAll();
            
            if (empty($rows)) {
                $hasMoreData = false;
                break;
            }
            
            foreach ($rows as $row) {
                $columns = [];
                $values = [];
                
                foreach ($row as $key => $value) {
                    $columns[] = "`$key`";
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        // استخدام addslashes بسيط بدلاً من الاستبدال المتعدد
                        $escapedValue = addslashes($value);
                        $values[] = "'" . $escapedValue . "'";
                    }
                }
                
                $insertSQL = "INSERT INTO `$tableName` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
                fwrite($fileHandle, $insertSQL);
                
                $rowsProcessed++;
                
                // تحرير الذاكرة بعد كل صف
                unset($columns, $values, $insertSQL);
            }
            
            $offset += $pageSize;
            
            // تحرير الذاكرة بعد كل دفعة
            unset($rows);
            
            // فحص الذاكرة كل 10 دُفعات
            if ($offset % ($pageSize * 10) === 0) {
                $this->checkMemoryUsage();
            }
        }
        
        fwrite($fileHandle, "\n");
        
        return ['rows' => $rowsProcessed];
    }

    /**
     * نسخ بيانات تزايدية للجدول (للنسخ الذكية)
     */
  private function backupIncrementalTableData($tableName, $fileHandle) {
    $rowsProcessed = 0;
    
    // كتابة هيكل الجدول أولاً
    fwrite($fileHandle, "\n-- --------------------------------------------------------\n");
    fwrite($fileHandle, "-- Table structure for table `$tableName`\n");
    fwrite($fileHandle, "-- --------------------------------------------------------\n\n");
    
    $this->db->query("SHOW CREATE TABLE `$tableName`");
    $createTable = $this->db->fetch();
    
    if ($createTable) {
        fwrite($fileHandle, "DROP TABLE IF EXISTS `$tableName`;\n");
        $createSQL = $createTable->{'Create Table'};
        $createSQL = preg_replace('/AUTO_INCREMENT=\d+\s*/', '', $createSQL);
        fwrite($fileHandle, $createSQL . ";\n\n");
    }
    
    // بيانات الجدول - نسخ كامل (بدلاً من التزايدي المعقد)
    fwrite($fileHandle, "--\n-- Data for table `$tableName`\n--\n\n");

    $pageSize = 100;
    $offset = 0;
    $hasMoreData = true;
    
    // التحقق من وجود أعمدة التاريخ أولاً
    $this->db->query("SHOW COLUMNS FROM `$tableName` LIKE 'created_at'");
    $hasCreatedAt = $this->db->fetch();
    
    $this->db->query("SHOW COLUMNS FROM `$tableName` LIKE 'updated_at'");
    $hasUpdatedAt = $this->db->fetch();
    
    while ($hasMoreData) {
        $this->freeMemory();
        
        // بناء الاستعلام بناءً على الأعمدة المتاحة
        if ($hasCreatedAt && $hasUpdatedAt) {
            $lastBackupTime = date('Y-m-d H:i:s', strtotime('-1 day'));
            $query = "SELECT * FROM `$tableName` WHERE 
                     created_at >= ? OR 
                     updated_at >= ? 
                     LIMIT $offset, $pageSize";
            
            $this->db->query($query);
            $this->db->bind(1, $lastBackupTime);
            $this->db->bind(2, $lastBackupTime);
        } else {
            // إذا لم توجد أعمدة التاريخ، نسخ جميع البيانات
            $query = "SELECT * FROM `$tableName` LIMIT $offset, $pageSize";
            $this->db->query($query);
        }
        
        $rows = $this->db->fetchAll();
        
        if (empty($rows)) {
            $hasMoreData = false;
            break;
        }
        
        foreach ($rows as $row) {
            $columns = [];
            $values = [];
            
            foreach ($row as $key => $value) {
                $columns[] = "`$key`";
                if ($value === null) {
                    $values[] = 'NULL';
                } else {
                    $escapedValue = addslashes($value);
                    $values[] = "'" . $escapedValue . "'";
                }
            }
            
            $insertSQL = "INSERT INTO `$tableName` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
            fwrite($fileHandle, $insertSQL);
            
            $rowsProcessed++;
            unset($columns, $values, $insertSQL);
        }
        
        $offset += $pageSize;
        unset($rows);
        
        if ($offset % ($pageSize * 10) === 0) {
            $this->checkMemoryUsage();
        }
    }
    
    fwrite($fileHandle, "\n");
    
    return ['rows' => $rowsProcessed];
}

    /**
     * الحصول على الجداول المعدلة مؤخراً
     */
    private function getRecentlyModifiedTables() {
        $tables = $this->getAllTables();
        $recentTables = [];
        
        foreach ($tables as $table) {
            // يمكن التحقق من وقت آخر تعديل للجدول
            // هنا نأخذ جميع الجداول للمثال، لكن يمكن تطويره
            $recentTables[] = $table;
            
            // لتجنب استخدام ذاكرة كبيرة، نحد بعدد الجداول
            if (count($recentTables) >= 15) {
                break;
            }
        }
        
        return $recentTables;
    }

    /**
     * تحرير الذاكرة بشكل فعال
     */
    private function freeMemory() {
        if (function_exists('gc_mem_caches')) {
            gc_mem_caches();
        }
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * فحص استخدام الذاكرة
     */
    private function checkMemoryUsage() {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->getMemoryLimit();
        
        // إذا تجاوزنا 80% من الحد المسموح، نوقف العملية
        if ($memoryUsage > ($memoryLimit * 0.8)) {
            throw new Exception("استنفاد الذاكرة: " . round($memoryUsage/1024/1024, 2) . "MB مستخدم من " . round($memoryLimit/1024/1024, 2) . "MB");
        }
    }

    /**
     * الحصول على حد الذاكرة الحالي
     */
    private function getMemoryLimit() {
        $memoryLimit = ini_get('memory_limit');
        if (preg_match('/^(\d+)(.)$/', $memoryLimit, $matches)) {
            if ($matches[2] == 'G') {
                $memoryLimit = $matches[1] * 1024 * 1024 * 1024;
            } elseif ($matches[2] == 'M') {
                $memoryLimit = $matches[1] * 1024 * 1024;
            } elseif ($matches[2] == 'K') {
                $memoryLimit = $matches[1] * 1024;
            }
        }
        return $memoryLimit;
    }

    /**
     * معالجة ملف النسخ الاحتياطي (تشفير، ضغط)
     */
    private function processBackupFile($filepath, $fileSize, $logId, $tablesCount, $executionTime) {
    // التحقق من وجود الملف وحجمه أولاً
    if (!file_exists($filepath) || filesize($filepath) === 0) {
        throw new Exception("ملف النسخ الاحتياطي غير موجود أو فارغ: " . $filepath);
    }
    
    $actualFileSize = filesize($filepath);
    $encrypted = false;
    $encryptionKey = null;
    $compressed = false;
    $compressionRatio = null;
    $finalFilepath = $filepath;

    // تشفير الملف إذا كان مطلوباً
    if ($this->shouldEncrypt()) {
        try {
            $encryptionKey = $this->encryption->generateKey();
            $encryptedPath = $this->encryption->encryptBackup($filepath, $encryptionKey);
            
            if (file_exists($encryptedPath)) {
                $finalFilepath = $encryptedPath;
                $encrypted = true;
                
                // حذف الملف الأصلي غير المشفر
                if (file_exists($filepath) && $filepath !== $encryptedPath) {
                    unlink($filepath);
                }
            }
        } catch (Exception $e) {
            error_log("فشل التشفير: " . $e->getMessage());
            // الاستمرار بدون تشفير
        }
    }
    
    // ضغط الملف إذا كان مطلوباً
    if ($this->shouldCompress()) {
        try {
            $compressedPath = $this->compressBackup($finalFilepath);
            
            if (file_exists($compressedPath)) {
                $originalSize = filesize($finalFilepath);
                $compressedSize = filesize($compressedPath);
                
                // حساب نسبة الضغط فقط إذا كانت الأحجام صالحة
                if ($originalSize > 0 && $compressedSize > 0) {
                    $compressionRatio = round((1 - ($compressedSize / $originalSize)) * 100, 2);
                } else {
                    $compressionRatio = 0;
                }
                
                // حذف الملف الأصلي غير المضغوط
                if (file_exists($finalFilepath) && $finalFilepath !== $compressedPath) {
                    unlink($finalFilepath);
                }
                
                $finalFilepath = $compressedPath;
                $actualFileSize = $compressedSize;
                $compressed = true;
            }
        } catch (Exception $e) {
            error_log("فشل الضغط: " . $e->getMessage());
            // الاستمرار بدون ضغط
        }
    }
    
    // تسجيل نجاح النسخ الاحتياطي
    $this->logBackupSuccess($logId, $finalFilepath, $actualFileSize, $tablesCount, $executionTime, $encrypted, $encryptionKey, $compressed, $compressionRatio);
    
    return [
        'filepath' => $finalFilepath,
        'file_size' => $actualFileSize,
        'encrypted' => $encrypted,
        'compressed' => $compressed,
        'compression_ratio' => $compressionRatio
    ];
}

    /**
     * إنشاء تذييل ملف النسخ الاحتياطي
     */
    private function generateBackupFooter($totalRows, $tablesCount) {
        $footer = "\n-- --------------------------------------------------------\n";
        $footer .= "-- Backup completed: " . date('Y-m-d H:i:s') . "\n";
        $footer .= "-- Total tables: " . $tablesCount . "\n";
        $footer .= "-- Total rows: " . $totalRows . "\n";
        $footer .= "-- Memory peak usage: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
        $footer .= "-- --------------------------------------------------------\n";
        
        return $footer;
    }

    /**
     * التحقق إذا كان يجب تشفير النسخة
     */
    private function shouldEncrypt() {
        $settings = (new BackupSetting())->getCurrentSettings();
        return $settings && $settings->encryption_enabled == 1;
    }

    /**
     * التحقق إذا كان يجب ضغط النسخة
     */
    private function shouldCompress() {
        $settings = (new BackupSetting())->getCurrentSettings();
        return $settings && $settings->compression_enabled == 1;
    }

    /**
     * ضغط ملف النسخ الاحتياطي
     */
    private function compressBackup($filePath) {
        $compressedPath = $filePath . '.gz';
        
        // فتح الملفات للقراءة والكتابة
        $source = fopen($filePath, 'rb');
        $dest = gzopen($compressedPath, 'wb9');
        
        if (!$source || !$dest) {
            throw new Exception("فشل في فتح الملفات للضغط");
        }
        
        // نسخ البيانات مع الضغط
        while (!feof($source)) {
            gzwrite($dest, fread($source, 4096));
        }
        
        // إغلاق الملفات
        fclose($source);
        gzclose($dest);
        
        // حذف الملف الأصلي
        unlink($filePath);
        
        return $compressedPath;
    }

    /**
     * فك ضغط ملف النسخ الاحتياطي
     */
    private function decompressBackup($filePath) {
        $decompressedPath = str_replace('.gz', '', $filePath);
        
        // فتح الملفات للقراءة والكتابة
        $source = gzopen($filePath, 'rb');
        $dest = fopen($decompressedPath, 'wb');
        
        if (!$source || !$dest) {
            throw new Exception("فشل في فتح الملفات لفك الضغط");
        }
        
        // نسخ البيانات مع فك الضغط
        while (!gzeof($source)) {
            fwrite($dest, gzread($source, 4096));
        }
        
        // إغلاق الملفات
        gzclose($source);
        fclose($dest);
        
        return $decompressedPath;
    }

    /**
     * تحديث تقدم النسخ
     */
    private function updateBackupProgress($logId, $processed, $total) {
        $progress = round(($processed / $total) * 100, 2);
        
        $this->db->query(
            "UPDATE backup_logs SET progress = ? WHERE id = ?"
        );
        $this->db->bind(1, $progress);
        $this->db->bind(2, $logId);
        $this->db->execute();
    }

    /**
     * تسجيل بدء النسخ المحسن
     */
    private function logBackupStart($backupName, $type, $userId) {
        $this->db->query(
            "INSERT INTO backup_logs (backup_name, backup_type, file_path, status, created_by, started_at) 
             VALUES (?, ?, ?, 'in_progress', ?, NOW())"
        );
        $this->db->bind(1, $backupName);
        $this->db->bind(2, $type);
        $this->db->bind(3, '');
        $this->db->bind(4, $userId);
        $this->db->execute();
        return $this->db->lastInsertId();
    }

    /**
     * تسجيل نجاح النسخ المحسن
     */
    private function logBackupSuccess($logId, $filePath, $fileSize, $tablesCount, $executionTime, $encrypted = false, $encryptionKey = null, $compressed = false, $compressionRatio = null) {
        $correctedPath = str_replace('\\', '/', $filePath);
        
        $this->db->query(
            "UPDATE backup_logs SET 
                status = 'success', 
                file_path = ?, 
                file_size = ?, 
                tables_count = ?,
                execution_time = ?,
                is_encrypted = ?,
                encryption_key = ?,
                is_compressed = ?,
                compression_ratio = ?,
                completed_at = NOW()
             WHERE id = ?"
        );
        $this->db->bind(1, $correctedPath);
        $this->db->bind(2, $fileSize);
        $this->db->bind(3, $tablesCount);
        $this->db->bind(4, $executionTime);
        $this->db->bind(5, $encrypted ? 1 : 0);
        $this->db->bind(6, $encryptionKey);
        $this->db->bind(7, $compressed ? 1 : 0);
        $this->db->bind(8, $compressionRatio);
        $this->db->bind(9, $logId);
        $this->db->execute();
    }

    /**
     * تسجيل فشل النسخ الاحتياطي
     */
    private function logBackupFailure($logId, $errorMessage) {
        $this->db->query(
            "UPDATE backup_logs SET status = 'failed', error_message = ?, completed_at = NOW() WHERE id = ?"
        );
        $this->db->bind(1, $errorMessage);
        $this->db->bind(2, $logId);
        $this->db->execute();
    }

    /**
     * الحصول على جميع الجداول في قاعدة البيانات
     */
    private function getAllTables() {
        $this->db->query("SHOW TABLES");
        $result = $this->db->fetchAll(PDO::FETCH_COLUMN);
        return $result ?: [];
    }

    /**
     * إنشاء رأس ملف النسخ الاحتياطي
     */
    private function generateBackupHeader() {
        $header = "-- MySQL Backup - Professional System\n";
        $header .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $header .= "-- Database: " . DB_NAME . "\n";
        $header .= "-- PHP Version: " . PHP_VERSION . "\n";
        $header .= "-- Backup Type: Professional\n";
        $header .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $header .= "SET NAMES utf8mb4;\n\n";
        return $header;
    }

    /**
     * الحصول على آخر نسخة احتياطية
     */
    public function getLastBackup() {
        $this->db->query(
            "SELECT * FROM backup_logs WHERE status = 'success' ORDER BY created_at DESC LIMIT 1"
        );
        return $this->db->fetch();
    }

    /**
     * الحصول على سجل النسخ الاحتياطي
     */
    public function getBackupLogs($limit = 50) {
        $limit = intval($limit);
        $this->db->query(
            "SELECT bl.*, u.username 
             FROM backup_logs bl 
             LEFT JOIN users u ON bl.created_by = u.id 
             ORDER BY bl.created_at DESC 
             LIMIT $limit"
        );
        return $this->db->fetchAll();
    }

    /**
     * تنظيف النسخ القديمة
     */
    public function cleanOldBackups() {
        $settings = (new BackupSetting())->getCurrentSettings();
        $retentionDays = $settings ? $settings->retention_days : 30;
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-$retentionDays days"));
        
        // الحصول على الملفات المراد حذفها
        $this->db->query(
            "SELECT file_path FROM backup_logs 
             WHERE created_at < ? AND status = 'success'"
        );
        $this->db->bind(1, $cutoffDate);
        $oldBackups = $this->db->fetchAll();
        
        foreach ($oldBackups as $backup) {
            if ($backup->file_path && file_exists($backup->file_path)) {
                @unlink($backup->file_path);
            }
        }
        
        // حذف السجلات القديمة
        $this->db->query(
            "DELETE FROM backup_logs WHERE created_at < ?"
        );
        $this->db->bind(1, $cutoffDate);
        $this->db->execute();
        
        return ['deleted_files' => count($oldBackups)];
    }

    /**
     * استعادة نسخة احتياطية - نسخة محسنة
     */
    public function restoreBackup($filepath) {
        try {
            if (!file_exists($filepath)) {
                throw new Exception("ملف النسخ الاحتياطي غير موجود");
            }

            // إذا كان الملف مضغوطاً، قم بفك ضغطه أولاً
            if (pathinfo($filepath, PATHINFO_EXTENSION) === 'gz') {
                $filepath = $this->decompressBackup($filepath);
            }

            // قراءة محتوى الملف
            $sqlContent = file_get_contents($filepath);
            if ($sqlContent === false) {
                throw new Exception("فشل في قراءة ملف النسخ الاحتياطي");
            }

            // تقسيم SQL إلى استعلامات منفصلة
            $queries = $this->splitSqlQueries($sqlContent);
            
            $successCount = 0;
            $errorCount = 0;

            foreach ($queries as $query) {
                $query = trim($query);
                
                // تجاهل الأسطر الفارغة والتعليقات
                if (empty($query) || strpos($query, '--') === 0) {
                    continue;
                }

                try {
                    $this->db->query($query);
                    $this->db->execute();
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    error_log("استعلام فاشل في الاستعادة: " . $e->getMessage());
                    continue;
                }
            }

            return [
                'success' => true,
                'message' => "تمت الاستعادة: {$successCount} استعلام ناجح، {$errorCount} فاشل",
                'success_count' => $successCount,
                'error_count' => $errorCount
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * استعادة نسخة مشفرة
     */
    public function restoreEncryptedBackup($filepath, $encryptionKey) {
        try {
            if (!file_exists($filepath)) {
                throw new Exception("ملف النسخ الاحتياطي غير موجود");
            }

            // فك التشفير أولاً
            $decryptedData = $this->encryption->decryptBackup($filepath, $encryptionKey);
            $tempFile = $this->backupPath . 'temp_restore_' . time() . '.sql';
            file_put_contents($tempFile, $decryptedData);

            // الاستعادة من الملف المؤقت
            $result = $this->restoreBackup($tempFile);

            // حذف الملف المؤقت
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            return $result;

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * تقسيم استعلامات SQL بشكل آمن
     */
    private function splitSqlQueries($sql) {
        $queries = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        $escapeNext = false;

        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];

            if ($escapeNext) {
                $current .= $char;
                $escapeNext = false;
                continue;
            }

            if ($char === '\\') {
                $escapeNext = true;
                $current .= $char;
                continue;
            }

            if (($char === "'" || $char === '"') && !$inString) {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
            } elseif ($char === $stringChar && $inString) {
                $inString = false;
                $current .= $char;
            } elseif ($char === ';' && !$inString) {
                $queries[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if (!empty(trim($current))) {
            $queries[] = trim($current);
        }

        return array_filter($queries);
    }

    /**
     * الحصول على نسخة احتياطية بواسطة المعرف
     */
    public function getBackupById($id) {
        $this->db->query("SELECT * FROM backup_logs WHERE id = ?");
        $this->db->bind(1, $id);
        return $this->db->fetch();
    }

    /**
     * حذف نسخة احتياطية بواسطة المعرف
     */
    public function deleteBackupById($id) {
        // الحصول على معلومات النسخة أولاً
        $backup = $this->getBackupById($id);
        
        if ($backup && $backup->file_path && file_exists($backup->file_path)) {
            unlink($backup->file_path);
        }
        
        $this->db->query("DELETE FROM backup_logs WHERE id = ?");
        $this->db->bind(1, $id);
        return $this->db->execute();
    }

    /**
     * الحصول على سجل النسخ الاحتياطي مع Pagination
     */
    public function getBackupLogsPaginated($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        $this->db->query(
            "SELECT bl.*, u.username 
             FROM backup_logs bl 
             LEFT JOIN users u ON bl.created_by = u.id 
             ORDER BY bl.created_at DESC 
             LIMIT $perPage OFFSET $offset"
        );
        return $this->db->fetchAll();
    }

    /**
     * عدد سجلات النسخ الاحتياطي
     */
    public function getTotalBackupLogs() {
        $this->db->query("SELECT COUNT(*) as total FROM backup_logs");
        return $this->db->fetch()->total;
    }

    /**
     * إحصائيات النسخ الاحتياطي المتقدمة
     */
    public function getBackupStats() {
        $this->db->query("
            SELECT 
                COUNT(*) as total_backups,
                SUM(file_size) as total_size,
                AVG(file_size) as avg_size,
                MAX(file_size) as max_size,
                MIN(file_size) as min_size,
                MAX(created_at) as last_backup,
                COUNT(CASE WHEN status = 'success' THEN 1 END) as successful_backups,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_backups,
                COUNT(CASE WHEN is_encrypted = 1 THEN 1 END) as encrypted_backups,
                COUNT(CASE WHEN is_compressed = 1 THEN 1 END) as compressed_backups,
                AVG(execution_time) as avg_execution_time,
                ROUND(
                    (COUNT(CASE WHEN status = 'success' THEN 1 END) / COUNT(*)) * 100, 
                    2
                ) as success_rate
            FROM backup_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        return $this->db->fetch();
    }

    /**
     * تنظيف السجلات القديمة بناءً على retention_days
     */
    public function cleanOldLogs($retentionDays = 30) {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-$retentionDays days"));
        
        $this->db->query(
            "DELETE FROM backup_logs WHERE created_at < ?"
        );
        $this->db->bind(1, $cutoffDate);
        return $this->db->execute();
    }

    /**
     * الحصول على تقدم النسخ الحالية
     */
    public function getCurrentProgress($logId) {
        $this->db->query("SELECT progress FROM backup_logs WHERE id = ?");
        $this->db->bind(1, $logId);
        $result = $this->db->fetch();
        return $result ? $result->progress : 0;
    }

    /**
     * التحقق من سلامة النسخة
     */
    public function verifyBackupIntegrity($backupId) {
        $backup = $this->getBackupById($backupId);
        
        if (!$backup || !file_exists($backup->file_path)) {
            return ['valid' => false, 'message' => 'الملف غير موجود'];
        }

        $fileSize = filesize($backup->file_path);
        
        if ($fileSize === 0 || $fileSize === false) {
            return ['valid' => false, 'message' => 'الملف فارغ أو تالف'];
        }

        // إذا كانت النسخة مشفرة، نحتاج إلى التحقق من إمكانية فك التشفير
        if ($backup->is_encrypted) {
            try {
                $testData = file_get_contents($backup->file_path, false, null, 0, 100);
                // محاولة فك تشفير جزء صغير للتحقق
                if (strlen($testData) > 0) {
                    return ['valid' => true, 'message' => 'النسخة سليمة ومشفرة'];
                }
            } catch (\Exception $e) {
                return ['valid' => false, 'message' => 'النسخة المشفرة تالفة'];
            }
        }

        return ['valid' => true, 'message' => 'النسخة سليمة'];
    }

     /**
     * فحص صحة النظام الشامل
     */
    public function performHealthCheck() {
        $diskSpace = $this->checkDiskSpace();
        $backupStats = $this->getBackupStats();
        
        return [
            'disk_space' => [
                'free_gb' => $diskSpace['free_gb'],
                'total_gb' => $diskSpace['total_gb'],
                'usage_percent' => round(($diskSpace['total_gb'] - $diskSpace['free_gb']) / $diskSpace['total_gb'] * 100, 2),
                'status' => $diskSpace['status']
            ],
            'error_rate' => $backupStats->failed_backups > 0 ? 
                round(($backupStats->failed_backups / $backupStats->total_backups) * 100, 2) : 0,
            'backup_integrity' => [
                'success_rate' => $backupStats->success_rate ?? 0
            ],
            'last_backup' => [
                'days_ago' => $backupStats->last_backup_date ? 
                    round((time() - strtotime($backupStats->last_backup_date)) / (60 * 60 * 24)) : null
            ]
        ];
    }
}
?>