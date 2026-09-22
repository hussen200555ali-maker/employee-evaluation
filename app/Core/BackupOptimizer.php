<?php
namespace App\Core;

class BackupOptimizer {
    
    public static function optimizeMemoryUsage() {
        // إغلاق الاتصالات غير الضرورية
        if (function_exists('gc_enable')) {
            gc_enable();
        }
        
        // تقليل استخدام الذاكرة
        ini_set('mysqlnd.collect_memory_statistics', '0');
        ini_set('mysqli.reconnect', '1');
        
        return [
            'memory_usage' => memory_get_usage(true),
            'peak_usage' => memory_get_peak_usage(true)
        ];
    }
    
    public static function preBackupCleanup() {
        // تنظيف الذاكرة المؤقتة قبل البدء
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        gc_collect_cycles();
    }
    
    public static function getTableSizeEstimate($tableName) {
        // تقدير حجم الجدول لتحديد استراتيجية النسخ
        $db = Database::getInstance();
        $db->query("SELECT 
            DATA_LENGTH + INDEX_LENGTH as size,
            TABLE_ROWS as rows
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
        $db->bind(1, DB_NAME);
        $db->bind(2, $tableName);
        
        $result = $db->fetch();
        return $result ? [
            'size_bytes' => $result->size,
            'rows' => $result->rows
        ] : ['size_bytes' => 0, 'rows' => 0];
    }
}
?>