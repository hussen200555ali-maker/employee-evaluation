<?php

/**
 * @file
 * @brief ملف تعريف المسارات المركزي للتطبيق (النسخة المنظمة والموحدة).
 * @description تم إعادة هيكلة هذا الملف لفصل مسارات إدارة القوالب عن مسارات باني النماذج،
 *              بالإضافة إلى تنظيم بقية الوحدات بشكل منطقي.
 * @version 3.0
 * @author Manus AI
 */

// إنشاء نسخة جديدة من الـ Router.
$router = new App\Core\Router();

/*
|==========================================================================
| 1. المسارات العامة والمصادقة (Public & Auth)
|==========================================================================
*/
$router->get('', 'App\Controllers\PagesController@home');
$router->get('about', 'App\Controllers\PagesController@about');
$router->get('login', 'App\Controllers\AuthController@showLoginForm');
$router->post('login', 'App\Controllers\AuthController@login');
// بند 4: الخروج عبر POST فقط (يمنع Logout-CSRF عبر رابط GET)
$router->post('logout', 'App\Controllers\AuthController@logout');
// بند 10: تغيير كلمة المرور (مصادقة مطلوبة) — يُلزم للسير قدمًا لمن طُلب منهم التغيير (بند 9) وللحُرّ متى شاؤوا
$router->get('change-password', 'App\Controllers\AuthController@showChangePassword');
$router->post('change-password', 'App\Controllers\AuthController@changePassword');
$router->get('dashboard', 'App\Controllers\DashboardController@index');

// --- بند 15: الإشعارات (شاشة + تعليم كمقروء المفرد/الكل عبر POST محمي CSRF) ---
$router->get('notifications', 'App\Controllers\NotificationController@index');
$router->post('notifications/read/:id', 'App\Controllers\NotificationController@markRead');
$router->post('notifications/read-all', 'App\Controllers\NotificationController@markAllRead');

/*
|==========================================================================
| 2. إدارة الهيكل التنظيمي (Organizational Structure)
|==========================================================================
*/
// --- الفروع (Branches) ---
$router->get('branches', 'App\Controllers\BranchController@index');
$router->post('branches', 'App\Controllers\BranchController@store');
$router->post('branches/update', 'App\Controllers\BranchController@update');
$router->post('branches/delete', 'App\Controllers\BranchController@destroy');

// --- الأقسام (Departments) ---
$router->get('departments', 'App\Controllers\DepartmentController@index');
$router->post('departments', 'App\Controllers\DepartmentController@store');
$router->post('departments/update', 'App\Controllers\DepartmentController@update');
$router->post('departments/delete', 'App\Controllers\DepartmentController@destroy');

/*
|==========================================================================
| 3. إدارة الموارد البشرية (Human Resources)
|==========================================================================
*/
// --- الموظفين (Employees) ---
$router->get('employees', 'App\Controllers\EmployeeController@index');
$router->get('employees/create', 'App\Controllers\EmployeeController@create');
$router->post('employees', 'App\Controllers\EmployeeController@store');
$router->get('employees/profile/:id', 'App\Controllers\EmployeeController@profile');
$router->post('employees/profile/:id/reports', 'App\Controllers\EmployeeReportController@generate');
$router->get('employees/profile/:id/reports/:reportId', 'App\Controllers\EmployeeReportController@show');
$router->get('employees/profile/:id/reports/:reportId/export/:format', 'App\Controllers\EmployeeReportController@export');
$router->get('employees/:id', 'App\Controllers\EmployeeController@show');
$router->get('employees/:id/edit', 'App\Controllers\EmployeeController@edit');
$router->post('employees/update', 'App\Controllers\EmployeeController@update');
$router->post('employees/delete', 'App\Controllers\EmployeeController@destroy');
 
// --- المناصب (Positions) ---
$router->get('employees/positions', 'App\Controllers\PositionsController@index');
$router->post('employees/positions/store', 'App\Controllers\PositionsController@store');
$router->post('employees/positions/update', 'App\Controllers\PositionsController@update');
$router->post('employees/positions/delete', 'App\Controllers\PositionsController@destroy');

/*
|==========================================================================
| 4. لوحة تحكم المدير (Admin Panel)
|==========================================================================
*/
// --- إدارة المستخدمين (Users) ---
$router->get('admin/users', 'App\Controllers\Admin\UserController@index');
$router->get('admin/users/create', 'App\Controllers\Admin\UserController@create');
$router->post('admin/users', 'App\Controllers\Admin\UserController@store');
$router->get('admin/users/edit/:id', 'App\Controllers\Admin\UserController@edit');
$router->post('admin/users/update', 'App\Controllers\Admin\UserController@update');
$router->post('admin/users/delete', 'App\Controllers\Admin\UserController@destroy');
$router->post('admin/users/update-role', 'App\Controllers\Admin\UserController@updateRole');
$router->post('admin/users/toggle-status', 'App\Controllers\Admin\UserController@toggleStatus');
$router->post('admin/users/reset-password', 'App\Controllers\Admin\UserController@resetPassword');

// --- إدارة الأدوار والصلاحيات (Roles & Permissions) ---
$router->get('admin/roles', 'App\Controllers\Admin\RoleController@index');
$router->get('admin/roles/create', 'App\Controllers\Admin\RoleController@create');
$router->post('admin/roles', 'App\Controllers\Admin\RoleController@store');
$router->post('admin/roles/update', 'App\Controllers\Admin\RoleController@update');
$router->post('admin/roles/delete', 'App\Controllers\Admin\RoleController@destroy');
$router->post('admin/roles/update-permission', 'App\Controllers\Admin\RoleController@updatePermission');

// --- grading scales ---
$router->get('grading-scales', 'App\Controllers\GradingScaleController@index');
$router->post('grading-scales', 'App\Controllers\GradingScaleController@store');
$router->post('grading-scales/delete', 'App\Controllers\GradingScaleController@destroy');
$router->get('reports/create', 'App\Controllers\ReportsController@create');
$router->get('reports/archive', 'App\Controllers\ReportsController@archive');
$router->post('reports/generate', 'App\Controllers\ReportsController@generate');
$router->get('reports/print/:id', 'App\Controllers\ReportsController@printOnly');
$router->get('reports/export/:id/:format', 'App\Controllers\ReportsController@export');

/*
|==========================================================================
| 5. وحدة القوالب والتقييم (Templates & Evaluations)
|==========================================================================
*/

// --- 5.1: إدارة القوالب (عرض القائمة والحذف) ---
// هذه المسارات تتعامل مع القوالب ككيانات، وتوجه إلى TemplateController.
$router->get('templates', 'App\Controllers\TemplateController@index');
$router->post('templates/delete', 'App\Controllers\TemplateController@destroy');

// --- 5.2: باني النماذج (تصميم وإنشاء وتعديل) ---
// كل ما يتعلق بواجهة الباني يتم توجيهه إلى BuilderController.
$router->get('builder/create', 'App\Controllers\BuilderController@index');
$router->get('builder/edit/:id', 'App\Controllers\BuilderController@index');
$router->post('builder/save', 'App\Controllers\BuilderController@save');
$router->get('builder/preview', 'App\Controllers\BuilderController@preview');

// --- 5.3: عمليات التقييم الفعلية ---
$router->get('evaluations/create', 'App\Controllers\EvaluationController@create');

// CD-022: Bulk Evaluation
$router->get('evaluations/bulk-create', 'App\Controllers\BulkEvaluationController@create');
$router->get('api/templates/:id/schema', 'App\Controllers\BulkEvaluationController@schema');
$router->post('api/employees/quick-add', 'App\Controllers\BulkEvaluationController@quickAdd');
$router->post('evaluations/save-single', 'App\Controllers\BulkEvaluationController@saveSingle');
$router->post('evaluations/bulk-save', 'App\Controllers\BulkEvaluationController@bulkSave');
$router->post('evaluations/store', 'App\Controllers\EvaluationController@store');
$router->get('evaluations/:id/fill', 'App\Controllers\EvaluationController@fill');
$router->post('evaluations/submit', 'App\Controllers\EvaluationController@submit');
$router->get('evaluations/show/:id', 'App\Controllers\EvaluationController@show');
$router->get('evaluations/print/:id', 'App\Controllers\EvaluationController@printView');
$router->post('evaluations/delete', 'App\Controllers\EvaluationController@destroy');
$router->post('evaluations/reopen', 'App\Controllers\EvaluationController@reopen');
 $router->get('evaluations/excel/import', 'App\Controllers\EvaluationExcelController@importForm');
 // تنزيل قالب Excel الديناميكي — يدعم ?template_id=X أو المسار المحدد أدناه
 $router->get('evaluations/excel/template', 'App\Controllers\EvaluationExcelController@downloadTemplate');
 $router->get('evaluations/excel/template/:id', 'App\Controllers\EvaluationExcelController@downloadTemplate');
 $router->post('evaluations/excel/preview', 'App\Controllers\EvaluationExcelController@preview');
 $router->post('evaluations/excel/commit/:id', 'App\Controllers\EvaluationExcelController@commit');
 $router->get('evaluations/excel/training-template', 'App\Controllers\EvaluationExcelController@downloadTrainingTemplate');
 $router->get('api/training-templates', 'App\Controllers\EvaluationExcelController@getTrainingTemplates');
 $router->get('api/training-list', 'App\Controllers\EvaluationExcelController@getTrainingList');

// --- 5.4: عرض سجلات التقييم ---
$router->get('evaluations', 'App\Controllers\EvaluationController@index');
$router->get('employees/:id/evaluations', 'App\Controllers\EmployeeController@showEmployeeEvaluations');
$router->get('employees/:id/evaluations/:year', 'App\Controllers\EmployeeController@showEmployeeEvaluationsByYear');

// --- 5.5: تقييم الوحدات (المرحلة 1) ---
$router->get('units/evaluations/create', 'App\Controllers\UnitEvaluationController@create');
$router->post('units/evaluations/store', 'App\Controllers\UnitEvaluationController@store');
$router->post('units/evaluations/review', 'App\Controllers\UnitEvaluationController@review');
$router->get('schedules', 'App\Controllers\ScheduleController@index');
$router->post('schedules/store', 'App\Controllers\ScheduleController@store');
$router->post('schedules/update', 'App\Controllers\ScheduleController@update');
$router->post('schedules/delete', 'App\Controllers\ScheduleController@destroy');

// --- 5.6: التقييمات المرنة للدورات/الورش (المرحلة 2 - 6 أنواع عامة) ---
$router->get('flexible/evaluations/create/course', 'App\Controllers\FlexibleEvaluationController@createCourse');
$router->post('flexible/evaluations/store/course', 'App\Controllers\FlexibleEvaluationController@storeCourse');
$router->get('flexible/evaluations/create/workshop', 'App\Controllers\FlexibleEvaluationController@createWorkshop');
$router->post('flexible/evaluations/store/workshop', 'App\Controllers\FlexibleEvaluationController@storeWorkshop');
$router->get('flexible/evaluations/create/employee-in-course', 'App\Controllers\FlexibleEvaluationController@createEmployeeInCourse');
$router->post('flexible/evaluations/store/employee-in-course', 'App\Controllers\FlexibleEvaluationController@storeEmployeeInCourse');
$router->get('flexible/evaluations/create/employee-in-workshop', 'App\Controllers\FlexibleEvaluationController@createEmployeeInWorkshop');
$router->post('flexible/evaluations/store/employee-in-workshop', 'App\Controllers\FlexibleEvaluationController@storeEmployeeInWorkshop');
// الأرشيف القديم (للقراءة فقط) يبقى: training/evaluations/show/:id

/*
|==========================================================================
| 6. وحدات التدريب (Training Modules)
|==========================================================================
*/
// --- الدورات التدريبية (Courses) ---
$router->get('training/courses', 'App\Controllers\TrainingCourseController@index');
$router->get('training/courses/create', 'App\Controllers\TrainingCourseController@create');
$router->post('training/courses', 'App\Controllers\TrainingCourseController@store');
$router->get('training/courses/edit/:id', 'App\Controllers\TrainingCourseController@edit');
$router->post('training/courses/update', 'App\Controllers\TrainingCourseController@update');
$router->post('training/courses/delete', 'App\Controllers\TrainingCourseController@destroy');
$router->post('training/courses/update-status', 'App\Controllers\TrainingCourseController@updateStatus');
// CD-033.1: تفاصيل الدورة + API (إلغاء enroll — الربط يتم أثناء التقييم)
$router->get('training/courses/:id', 'App\Controllers\TrainingCourseController@show');
$router->get('api/courses/:id/pending-employees', 'App\Controllers\TrainingCourseController@pendingEmployees');

// --- كتالوج الدورات (Course Catalog) ---
$router->get('training/courses/catalog', 'App\Controllers\CourseCatalogController@index');
$router->get('training/courses/catalog/create', 'App\Controllers\CourseCatalogController@create');
$router->post('training/courses/catalog/store', 'App\Controllers\CourseCatalogController@store');
$router->get('training/courses/catalog/edit/:id', 'App\Controllers\CourseCatalogController@edit');
$router->post('training/courses/catalog/update', 'App\Controllers\CourseCatalogController@update');
$router->post('training/courses/catalog/delete', 'App\Controllers\CourseCatalogController@delete');

// --- تقييمات الدورات (Course Evaluations) ---
$router->get('training/evaluations/create/:course_id', 'App\Controllers\CourseEvaluationController@create');
$router->post('training/evaluations/store', 'App\Controllers\CourseEvaluationController@store');
$router->get('training/evaluations/show/:id', 'App\Controllers\CourseEvaluationController@show');

// --- ورش العمل (Workshops) ---
$router->get('training/workshops', 'App\Controllers\TrainingWorkshopController@index');
$router->get('training/workshops/create', 'App\Controllers\TrainingWorkshopController@create');
$router->post('training/workshops/store', 'App\Controllers\TrainingWorkshopController@store');
$router->get('training/workshops/edit/:id', 'App\Controllers\TrainingWorkshopController@edit');
$router->post('training/workshops/update', 'App\Controllers\TrainingWorkshopController@update');
$router->post('training/workshops/delete', 'App\Controllers\TrainingWorkshopController@delete');

// --- كتالوج الورش (Workshop Catalog) ---
$router->get('training/workshops/catalog', 'App\Controllers\WorkshopCatalogController@index');
$router->post('training/workshops/catalog/store', 'App\Controllers\WorkshopCatalogController@store');
$router->post('training/workshops/catalog/update', 'App\Controllers\WorkshopCatalogController@update');
$router->post('training/workshops/catalog/delete', 'App\Controllers\WorkshopCatalogController@delete');

// --- تقييمات الورش (Workshop Evaluations) ---
$router->get('training/workshops/evaluations/create/:workshop_id', 'App\Controllers\WorkshopEvaluationController@create');
$router->post('training/workshops/evaluations/store', 'App\Controllers\WorkshopEvaluationController@store');
$router->get('training/workshops/evaluations/show/:id', 'App\Controllers\WorkshopEvaluationController@show');

/*
|==========================================================================
| 7. التقارير والتحليل (Reports & Analysis)
|==========================================================================
*/
// --- التقارير المؤسسية الشاملة ---
// --- التقارير الموحدة (المرحلة 3) ---
// --- CD-008: Report Builder + Core Reports (CP-B) ---
// --- التقارير الإحصائية الرسمية + ملفات الأداء (المرحلة 4) ---

// --- تقارير التدريب ---

// --- تقارير الورش ---

// --- التحليل الاستراتيجي للأداء ---

/*
|==========================================================================
| 8. النسخ الاحتياطي المتكامل (Integrated Backup System)
|==========================================================================
*/

// --- 8.1: الواجهات الأساسية ---
$router->get('backup', 'App\Controllers\BackupController@index');
$router->get('backup/dashboard', 'App\Controllers\BackupController@proDashboard');
$router->get('backup/settings', 'App\Controllers\BackupController@settings');
$router->post('backup/settings/save', 'App\Controllers\BackupController@saveSettings');

// --- 8.2: أنواع النسخ ---
// CD-026: تحويل مسارات النسخ التدميرية من GET إلى POST (حماية CSRF)
$router->post('backup/full', 'App\Controllers\BackupController@createFullBackup');
$router->post('backup/incremental', 'App\Controllers\BackupController@createIncrementalBackup');
$router->post('backup/optimized', 'App\Controllers\BackupController@createOptimizedBackup');
$router->post('backup/smart', 'App\Controllers\BackupController@createSmartBackup');

// --- 8.3: إدارة الملفات ---
$router->get('backup/download/:id', 'App\Controllers\BackupController@downloadBackup');
$router->post('backup/download/:id', 'App\Controllers\BackupController@downloadBackup');
$router->post('backup/restore/:id', 'App\Controllers\BackupController@restoreBackup');
$router->post('backup/delete/:id', 'App\Controllers\BackupController@deleteBackup');
$router->post('backup/cleanup', 'App\Controllers\BackupController@cleanOldBackups');
$router->post('backup/clean-backups', 'App\Controllers\BackupController@cleanBackups');

// --- 8.4: الجدولة التلقائية ---
$router->get('backup/scheduler-status', 'App\Controllers\BackupController@schedulerStatus');
$router->post('backup/setup-scheduler', 'App\Controllers\BackupController@setupScheduler');
$router->post('backup/remove-scheduler', 'App\Controllers\BackupController@removeScheduler');
$router->post('backup/test-scheduler', 'App\Controllers\BackupController@testScheduler');

// --- 8.5: الصحة والفحوصات ---
$router->get('backup/verify', 'App\Controllers\BackupController@verifyIntegrity');
$router->get('backup/health', 'App\Controllers\BackupController@healthCheck');
$router->get('backup/health/reports', 'App\Controllers\BackupController@healthReports');
$router->get('backup/check-disk', 'App\Controllers\BackupController@checkDisk');

// --- 8.6: إدارة التشفير ---
$router->get('backup/encryption', 'App\Controllers\BackupController@encryptionManager');
$router->post('backup/encryption/generate-key', 'App\Controllers\BackupController@generateEncryptionKey');
$router->get('backup/encryption/keys', 'App\Controllers\BackupController@encryptionKeys');
$router->post('backup/encryption/delete-key/:id', 'App\Controllers\BackupController@deleteEncryptionKey');

// --- 8.7: واجهات API ---
$router->get('api/backup/stats', 'App\Controllers\BackupController@getBackupStatsApi');
$router->get('api/backup/progress/:id', 'App\Controllers\BackupController@getBackupProgress');
$router->post('api/backup/quick-action', 'App\Controllers\BackupController@quickAction');

/*
|==========================================================================
| 9. واجهة برمجة التطبيقات (REST API v1)
|==========================================================================
*/
// --- REST API v1 (مؤمن بـ X-API-Key) ---
$router->get('api/v1/health', 'App\Controllers\Api\V1Controller@health');
$router->get('api/v1/employees', 'App\Controllers\Api\V1Controller@listEmployees');
$router->get('api/v1/employees/:id', 'App\Controllers\Api\V1Controller@showEmployee');
$router->get('api/v1/evaluations', 'App\Controllers\Api\V1Controller@listEvaluations');
$router->get('api/v1/evaluations/:id', 'App\Controllers\Api\V1Controller@showEvaluation');

/*
|==========================================================================
| 10. واجهة برمجة التطبيقات (API for JavaScript)
|==========================================================================
*/
// --- API عامة ---
$router->get('csrf/token', 'App\Controllers\AuthController@csrfToken');
$router->get('api/departments/by-branch/:branch_id', 'App\Controllers\ApiController@getDepartmentsByBranch');
$router->get('api/employees/search', 'App\Controllers\ApiController@searchEmployees');
$router->get('api/templates/by-position/:position_id', 'App\Controllers\ApiController@getTemplatesByPosition');
$router->get('api/templates/by-scope/:scope', 'App\Controllers\ApiController@getTemplatesByScope');
$router->get('api/to-hijri', 'App\Controllers\ApiController@toHijri');

// --- API خاصة بالتقارير ---

// --- API خاصة بالتحليل ---

/*
|==========================================================================
| إعدادات التصنيف الديناميكي
|==========================================================================
*/
$router->get('settings/rating', 'App\Controllers\SettingsController@rating');
$router->post('settings/rating/save', 'App\Controllers\SettingsController@saveRating');

/*
|==========================================================================
| أرشيف الدورات والورش
|==========================================================================
*/


/*
|==========================================================================
| التقارير الإدارية (جديد)
|==========================================================================
*/

/*
|==========================================================================
| إرجاع كائن الـ Router
|==========================================================================
*/
return $router;
