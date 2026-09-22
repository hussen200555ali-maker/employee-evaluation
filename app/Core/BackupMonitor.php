<?php
namespace App\Core;

class BackupMonitor {
    
    public function checkDiskSpace() {
        $freeSpace = disk_free_space(__DIR__);
        $totalSpace = disk_total_space(__DIR__);
        
        $freeGB = round($freeSpace / 1024 / 1024 / 1024, 2);
        $totalGB = round($totalSpace / 1024 / 1024 / 1024, 2);
        
        return [
            'free_gb' => $freeGB,
            'total_gb' => $totalGB,
            'status' => ($freeGB > 1) ? 'healthy' : (($freeGB > 0.5) ? 'warning' : 'critical')
        ];
    }
    
    public function performHealthCheck() {
        $diskSpace = $this->checkDiskSpace();
        
        // الحصول على إحصائيات النسخ
        $backupModel = new \App\Models\Backup();
        $stats = $backupModel->getBackupStats();
        
        // إصلاح هنا: استخدام last_backup بدلاً من last_backup_date
        return [
            'disk_space' => [
                'free_gb' => $diskSpace['free_gb'],
                'total_gb' => $diskSpace['total_gb'],
                'usage_percent' => round(($diskSpace['total_gb'] - $diskSpace['free_gb']) / $diskSpace['total_gb'] * 100, 2),
                'status' => $diskSpace['status']
            ],
            'error_rate' => $stats->failed_backups > 0 ? 
                round(($stats->failed_backups / $stats->total_backups) * 100, 2) : 0,
            'backup_integrity' => [
                'success_rate' => $stats->success_rate ?? 0
            ],
            'last_backup' => [
                'days_ago' => $stats->last_backup ? 
                    round((time() - strtotime($stats->last_backup)) / (60 * 60 * 24)) : null,
                'date' => $stats->last_backup // إضافة هذا للحصول على التاريخ
            ]
        ];
    }

    // باقي الدوال تبقى كما هي...
    public function verifyBackupIntegrity() {
        $backupModel = new \App\Models\Backup();
        $logs = $backupModel->getBackupLogs();
        
        $verified = 0;
        $total = 0;
        $results = [];
        
        foreach ($logs as $log) {
            if ($log->status === 'success' && $log->file_path) {
                $total++;
                $integrityCheck = $backupModel->verifyBackupIntegrity($log->id);
                
                if ($integrityCheck['valid']) {
                    $verified++;
                    $results[] = [
                        'backup_id' => $log->id,
                        'backup_name' => $log->backup_name,
                        'status' => 'valid',
                        'message' => $integrityCheck['message']
                    ];
                } else {
                    $results[] = [
                        'backup_id' => $log->id,
                        'backup_name' => $log->backup_name,
                        'status' => 'invalid',
                        'message' => $integrityCheck['message']
                    ];
                }
            }
        }
        
        $successRate = $total > 0 ? round(($verified / $total) * 100, 2) : 0;
        
        return [
            'success_rate' => $successRate,
            'verified' => $verified,
            'total' => $total,
            'results' => $results
        ];
    }

    public function logBackupResult($result) {
        error_log("Backup Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED'));
    }

    public function getDatabaseSize() {
        $db = \App\Core\Database::getInstance();
        $db->query("
            SELECT SUM(data_length + index_length) as size
            FROM information_schema.TABLES 
            WHERE table_schema = ?
        ");
        $db->bind(1, DB_NAME);
        $result = $db->fetch();
        
        return $result ? $result->size : 0;
    }
    
    public function checkLastBackup() {
        $backupModel = new \App\Models\Backup();
        $lastBackup = $backupModel->getLastBackup();
        
        if ($lastBackup) {
            $daysAgo = round((time() - strtotime($lastBackup->created_at)) / (60 * 60 * 24));
            return [
                'exists' => true,
                'days_ago' => $daysAgo,
                'backup' => $lastBackup,
                'last_backup_date' => $lastBackup->created_at // إضافة هذا السطر
            ];
        }
        
        return [
            'exists' => false, 
            'days_ago' => null,
            'last_backup_date' => null // إضافة هذا السطر
        ];
    }

    public function logHealthCheck($checkType, $status, $details) {
        try {
            $db = \App\Core\Database::getInstance();
            $db->query(
                "INSERT INTO backup_health_logs (check_type, status, details) 
                 VALUES (?, ?, ?)"
            );
            $db->bind(1, $checkType);
            $db->bind(2, $status);
            $db->bind(3, json_encode($details));
            return $db->execute();
        } catch (\Exception $e) {
            error_log("Failed to log health check: " . $e->getMessage());
            return false;
        }
    }

    public function performScheduledHealthCheck() {
        $healthData = $this->performHealthCheck();
        
        $overallStatus = 'healthy';
        if ($healthData['disk_space']['status'] === 'critical' || 
            $healthData['error_rate'] > 20) {
            $overallStatus = 'critical';
        } elseif ($healthData['disk_space']['status'] === 'warning' || 
                  $healthData['error_rate'] > 5) {
            $overallStatus = 'warning';
        }
        
        $this->logHealthCheck('scheduled_check', $overallStatus, $healthData);
        
        return $healthData;
    }
}