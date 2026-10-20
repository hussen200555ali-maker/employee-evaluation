<?php
namespace App\Controllers;

class BackupDashboardController extends Controller {
    
    public function index() {
        Auth::enforce('backup.manage');
        
        $manager = new \App\Core\BackupManager();
        $monitor = new \App\Core\BackupMonitor();
        
        $data = [
            'pageTitle' => 'لوحة التحكم الاحترافية',
            'health' => $monitor->performHealthCheck(),
            'stats' => $this->getBackupStats(),
            'recentBackups' => $this->getRecentBackups(10),
            'recommendations' => $this->getSystemRecommendations()
        ];
        
        $this->view('backup/pro_dashboard', $data);
    }
    
    /**
     * النسخ الذكي التلقائي
     */
    public function smartBackup() {
        Auth::enforce('backup.manage');
        
        try {
            $manager = new \App\Core\BackupManager();
            $result = $manager->createBackup('auto', Auth::id());
            
            echo json_encode([
                'success' => true,
                'message' => 'تم النسخ الذكي بنجاح',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * فحص سلامة النسخ
     */
    public function verifyIntegrity() {
        Auth::enforce('backup.manage');
        
        $monitor = new \App\Core\BackupMonitor();
        $results = $monitor->verifyBackupIntegrity();
        
        echo json_encode([
            'success' => true,
            'results' => $results
        ]);
    }
}