<?php
namespace App\Models;

use App\Core\Model;

class BackupLog extends Model {
    
    /**
     * الحصول على سجلات النسخ مع التصفية المتقدمة
     */
    public function getAdvancedLogs($filters = [], $page = 1, $perPage = 20) {
        $whereConditions = [];
        $bindings = [];
        
        // بناء شروط التصفية
        if (!empty($filters['status'])) {
            $whereConditions[] = "bl.status = ?";
            $bindings[] = $filters['status'];
        }
        
        if (!empty($filters['backup_type'])) {
            $whereConditions[] = "bl.backup_type = ?";
            $bindings[] = $filters['backup_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = "bl.created_at >= ?";
            $bindings[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = "bl.created_at <= ?";
            $bindings[] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (!empty($filters['user_id'])) {
            $whereConditions[] = "bl.created_by = ?";
            $bindings[] = $filters['user_id'];
        }
        
        $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
        $offset = ($page - 1) * $perPage;
        
        $query = "
            SELECT 
                bl.*, 
                u.username,
                u.full_name,
                bs.backup_type as schedule_type,
                bs.schedule_type as schedule_frequency
            FROM backup_logs bl 
            LEFT JOIN users u ON bl.created_by = u.id 
            LEFT JOIN backup_settings bs ON bl.settings_id = bs.id
            {$whereClause}
            ORDER BY bl.created_at DESC 
            LIMIT {$perPage} OFFSET {$offset}
        ";
        
        $this->db->query($query);
        
        foreach ($bindings as $index => $value) {
            $this->db->bind($index + 1, $value);
        }
        
        return $this->db->fetchAll();
    }
    
    /**
     * إحصائيات متقدمة للنسخ
     */
    public function getAdvancedStats($period = '30days') {
        $dateCondition = $this->getDateCondition($period);
        
        $query = "
            SELECT 
                -- الإحصائيات الأساسية
                COUNT(*) as total_backups,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_backups,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_backups,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_backups,
                
                -- إحصائيات الحجم
                SUM(file_size) as total_size,
                AVG(file_size) as avg_size,
                MAX(file_size) as max_size,
                MIN(file_size) as min_size,
                
                -- إحصائيات الأداء
                AVG(execution_time) as avg_execution_time,
                MAX(execution_time) as max_execution_time,
                
                -- إحصائيات النوع
                SUM(CASE WHEN backup_type = 'full' THEN 1 ELSE 0 END) as full_backups,
                SUM(CASE WHEN backup_type = 'incremental' THEN 1 ELSE 0 END) as incremental_backups,
                
                -- معدل النجاح
                ROUND(
                    (SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 
                    2
                ) as success_rate,
                
                -- آخر نسخة
                MAX(created_at) as last_backup_date
                
            FROM backup_logs 
            WHERE created_at >= {$dateCondition}
        ";
        
        $this->db->query($query);
        return $this->db->fetch();
    }
    
    /**
     * تحليل الاتجاهات
     */
    public function getTrendAnalysis($period = '90days') {
        $dateCondition = $this->getDateCondition($period);
        
        $query = "
            SELECT 
                DATE(created_at) as backup_date,
                COUNT(*) as daily_count,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as daily_success,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as daily_failed,
                AVG(file_size) as avg_daily_size,
                AVG(execution_time) as avg_daily_time
            FROM backup_logs 
            WHERE created_at >= {$dateCondition}
            GROUP BY DATE(created_at)
            ORDER BY backup_date DESC
            LIMIT 90
        ";
        
        $this->db->query($query);
        return $this->db->fetchAll();
    }
    
    /**
     * الحصول على الأخطاء المتكررة
     */
    public function getRecurringErrors() {
        $query = "
            SELECT 
                error_message,
                COUNT(*) as error_count,
                MIN(created_at) as first_occurrence,
                MAX(created_at) as last_occurrence
            FROM backup_logs 
            WHERE status = 'failed' 
                AND error_message IS NOT NULL
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY error_message
            HAVING error_count > 1
            ORDER BY error_count DESC
            LIMIT 10
        ";
        
        $this->db->query($query);
        return $this->db->fetchAll();
    }
    
    /**
     * تقرير الأداء التفصيلي
     */
    public function getPerformanceReport() {
        $query = "
            SELECT 
                -- الأداء حسب النوع
                backup_type,
                COUNT(*) as count,
                AVG(execution_time) as avg_time,
                AVG(file_size) as avg_size,
                ROUND(
                    (SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 
                    2
                ) as success_rate,
                
                -- الأداء حسب الوقت
                HOUR(created_at) as hour_of_day,
                COUNT(*) as hourly_count,
                AVG(execution_time) as hourly_avg_time
                
            FROM backup_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY backup_type, HOUR(created_at)
            ORDER BY backup_type, hour_of_day
        ";
        
        $this->db->query($query);
        return $this->db->fetchAll();
    }
    
    /**
     * تنظيف السجلات القديمة
     */
    public function cleanupOldLogs($retentionDays = 365) {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-$retentionDays days"));
        
        $this->db->query(
            "DELETE FROM backup_logs WHERE created_at < ?"
        );
        $this->db->bind(1, $cutoffDate);
        
        return $this->db->execute();
    }
    
    /**
     * تصدير السجلات
     */
    public function exportLogs($filters = [], $format = 'csv') {
        $logs = $this->getAdvancedLogs($filters, 1, 10000); // جميع السجلات
        
        $headers = [
            'ID', 'اسم النسخة', 'النوع', 'الحالة', 'الحجم (MB)', 
            'المستخدم', 'وقت التنفيذ', 'التاريخ', 'الرسالة'
        ];
        
        $data = [];
        foreach ($logs as $log) {
            $data[] = [
                $log->id,
                $log->backup_name,
                $log->backup_type === 'full' ? 'كاملة' : 'تزايدية',
                $log->status === 'success' ? 'ناجحة' : ($log->status === 'failed' ? 'فاشلة' : 'قيد التنفيذ'),
                round($log->file_size / 1024 / 1024, 2),
                $log->username ?? 'النظام',
                $log->execution_time ? $log->execution_time . ' ثانية' : 'غير معروف',
                date('Y-m-d H:i', strtotime($log->created_at)),
                $log->error_message ?? 'بدون أخطاء'
            ];
        }
        
        return [
            'headers' => $headers,
            'data' => $data,
            'total' => count($data)
        ];
    }
    
    /**
     * شرط التاريخ للإحصائيات
     */
    private function getDateCondition($period) {
        switch ($period) {
            case '7days':
                return "DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case '30days':
                return "DATE_SUB(NOW(), INTERVAL 30 DAY)";
            case '90days':
                return "DATE_SUB(NOW(), INTERVAL 90 DAY)";
            case '1year':
                return "DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return "DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }
    }
}
?>