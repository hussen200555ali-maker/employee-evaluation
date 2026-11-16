<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;
use App\Core\Audit;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\Branch;
use App\Models\GradingScale;
use App\Models\Template;
use App\Models\TrainingProgram;
use App\Services\EvaluationExcelService;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
final class EvaluationExcelController extends Controller
{
    private EvaluationExcelService $excel;
    public function __construct()
    {
        $this->excel = new EvaluationExcelService();
    }
    public function importForm(): void
    {
        Auth::enforce('evaluations.fill');
        $templates = (new Template())->getAllActiveWithType();
        $branches = (new Branch())->getAllWithDepartments();
        $evaluatorName = '';
        $evaluatorTitle = '';
        $evaluatorEmployeeId = 0;
        $user = Auth::user();
        if ($user && !empty($user->employee_id)) {
            $empModel = new \App\Models\Employee();
            $emp = $empModel->findById((int)$user->employee_id);
            if ($emp) {
                $evaluatorName = $emp->full_name;
                $evaluatorTitle = $emp->position_title ?? '';
                $evaluatorEmployeeId = (int)$user->employee_id;
            }
        }
        $templateStructures = [];
        foreach ($templates as $tpl) {
            try {
                $templateStructures[(int)$tpl->id] = $this->excel->describeTemplateStructure((int)$tpl->id, $evaluatorEmployeeId);
            } catch (\Throwable $e) {
                error_log('describeTemplateStructure error: ' . $e->getMessage());
            }
        }
        $this->view('evaluations/excel_import', [
            'pageTitle'      => 'استيراد نتائج التقييم',
            'csrfToken'      => Auth::generateCsrfToken(),
            'preview'        => null,
            'templates'      => $templates,
            'branches'       => $branches,
            'evaluatorName'  => $evaluatorName,
            'evaluatorTitle' => $evaluatorTitle,
            'gradingScales'  => (new GradingScale())->getActive(),
            'templateStructures' => $templateStructures,
        ]);
    }
    public function downloadTemplate($id = null): void
    {
        Auth::enforce('evaluations.fill');
        $employeeId = $this->currentEmployeeId();
        if ($employeeId <= 0) {
            Flash::set('لا يرتبط حسابك بموظف مقيّم صالح لتصدير قالب النتائج.', 'danger');
            $this->redirect('/evaluations/excel/import');
            return;
        }
        try {
            $templateId = (int) ($id ?? $_GET['template_id'] ?? 0);
            if ($templateId <= 0) {
                $templates = (new Template())->getAll();
                $templateId = (int) ($templates[0]->id ?? 0);
            }
            if ($templateId <= 0) {
                throw new RuntimeException('لا توجد قوالب تقييم نشطة لإنشاء القالب.');
            }
            $branchId = isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? (int) $_GET['branch_id'] : null;
            $departmentId = isset($_GET['department_id']) && $_GET['department_id'] !== '' ? (int) $_GET['department_id'] : null;
            if ($branchId !== null && $branchId <= 0) $branchId = null;
            if ($departmentId !== null && $departmentId <= 0) $departmentId = null;
            $maxRows = isset($_GET['max_rows']) && $_GET['max_rows'] !== '' ? (int) $_GET['max_rows'] : 100;
            $maxRows = max(1, min($maxRows, 2000));
            $book = $this->excel->buildTemplate($templateId, $employeeId, $branchId, $departmentId, $maxRows);
        } catch (RuntimeException $e) {
            Flash::set($e->getMessage(), 'danger');
            $this->redirect('/evaluations/excel/import');
            return;
        } catch (\Throwable $e) {
            error_log('Excel download error: ' . $e->getMessage());
            Flash::set('تعذر إنشاء القالب. يرجى المحاولة مرة أخرى.', 'danger');
            $this->redirect('/evaluations/excel/import');
            return;
        }
        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="evaluation-template-' . $templateId . '.xlsx"');
        header('X-Content-Type-Options: nosniff');
        (new Xlsx($book))->save('php://output');
        exit;
    }
    public function preview(): void
    {
        Auth::enforce('evaluations.fill');
        if (!Auth::validateCsrfToken($_POST['csrf_token'] ?? $_POST['_token'] ?? null)) {
            Flash::set('رمز الحماية غير صالح أو منتهي. يرجى تحديث الصفحة والمحاولة مرة أخرى.', 'danger');
            $this->redirect('/evaluations/excel/import');
            return;
        }
        $employeeId = $this->currentEmployeeId();
        try {
            $preview = $this->excel->previewUpload($_FILES['workbook'] ?? [], (int) Auth::id(), $employeeId);
        } catch (RuntimeException $e) {
            $msg = $this->friendlyPreviewMessage($e->getMessage());
            Flash::set($msg, 'danger');
            $this->redirect('/evaluations/excel/import');
            return;
        } catch (\Throwable $e) {
            error_log('Excel preview error: ' . $e->getMessage());
            Flash::set('حدث خطأ غير متوقع أثناء معالجة الملف. يرجى التأكد من صيغة XLSX وإعادة المحاولة.', 'danger');
            $this->redirect('/evaluations/excel/import');
            return;
        }
        $templates = (new Template())->getAllActiveWithType();
        $branches = (new Branch())->getAllWithDepartments();
        $evaluatorName2 = '';
        $evaluatorTitle2 = '';
        $user2 = Auth::user();
        if ($user2 && !empty($user2->employee_id)) {
            $empModel2 = new \App\Models\Employee();
            $emp2 = $empModel2->findById((int)$user2->employee_id);
            if ($emp2) {
                $evaluatorName2 = $emp2->full_name;
                $evaluatorTitle2 = $emp2->position_title ?? '';
            }
        }
        $this->view('evaluations/excel_import', [
            'pageTitle'      => 'معاينة استيراد النتائج',
            'csrfToken'      => Auth::generateCsrfToken(),
            'preview'        => $preview,
            'templates'      => $templates,
            'branches'       => $branches,
            'evaluatorName'  => $evaluatorName2,
            'evaluatorTitle' => $evaluatorTitle2,
            'gradingScales'  => (new GradingScale())->getActive(),
        ]);
    }
    private function friendlyPreviewMessage(string $raw): string
    {
        $raw = trim($raw);
        if (mb_strpos($raw, 'لا يحتوي الملف على أي صفوف') !== false) {
            return 'الملف لا يحتوي على بيانات قابلة للاستيراد — يرجى تعبئة صف واحد على الأقل: الرقم الوظيفي أو الاسم الكامل + تاريخ التقييم (YYYY-MM-DD أو DD-MM-YYYY) + قيم بنود التقييم، ثم إعادة الرفع. تلميح: حمّل القالب مع تعبئة من فرع لتعبئة الموظفين تلقائياً.';
        }
        if (mb_strpos($raw, 'حجم ملف') !== false) return $raw;
        if (mb_strpos($raw, 'صيغة الملف') !== false) return $raw . ' — يرجى تنزيل القالب من النظام وعدم تغيير امتداده.';
        if (mb_strpos($raw, 'تعذر قراءة') !== false) return $raw . ' — تأكد أن الملف غير تالف.';
        if (mb_strpos($raw, 'ورقة البيانات') !== false) return $raw . ' — استخدم القالب المنزّل من النظام.';
        if (mb_strpos($raw, 'ترويسات') !== false) return $raw;
        if (mb_strpos($raw, 'قالب التقييم') !== false) return $raw;
        return $raw;
    }
    public function commit($batchId): void
    {
        Auth::enforce('evaluations.fill');
        if (!Auth::validateCsrfToken($_POST['csrf_token'] ?? $_POST['_token'] ?? null)) {
            Flash::set('رمز الحماية غير صالح أو منتهي. يرجى تحديث الصفحة والمحاولة مرة أخرى.', 'danger');
            $this->redirect('/evaluations/excel/import');
            return;
        }
        try {
            $result = $this->excel->commitBatch((int) $batchId, (int) Auth::id(), $this->currentEmployeeId());
        } catch (RuntimeException $e) {
            Flash::set($e->getMessage(), 'danger');
            $this->redirect('/evaluations/excel/import');
            return;
        } catch (\Throwable $e) {
            error_log('Excel commit error: ' . $e->getMessage());
            Flash::set('حدث خطأ أثناء الالتزام. يرجى إعادة المعاينة والمحاولة.', 'danger');
            $this->redirect('/evaluations/excel/import');
            return;
        }
        $message = 'تم استيراد ' . $result['committed_rows'] . ' صفاً ضمن ' . $result['committed_evaluations'] . ' تقييمات بنجاح.';
        if ($result['created_employees'] > 0) { $message .= ' (تم إنشاء ' . $result['created_employees'] . ' موظفين جدد)'; }
        Flash::set($message . '.', 'success');
        $this->redirect('/evaluations');
    }
    private function currentEmployeeId(): int
    {
        $user = Auth::user();
        $employeeId = (int) ($user->employee_id ?? 0);
        if ($employeeId > 0) {
            return $employeeId;
        }
        $pdo = \App\Core\Database::getInstance()->getPdo();
        $stmt = $pdo->query("SELECT id FROM employees WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? (int) $row['id'] : 0;
    }
    public function downloadTrainingTemplate(): void
    {
        Auth::enforce('evaluations.fill');
        $employeeId = $this->currentEmployeeId();
        if ($employeeId <= 0) {
            Flash::set('لا يرتبط حسابك بموظف مقيّم صالح لتصدير قالب التقييم.', 'danger');
            $this->redirect('/training/courses');
            return;
        }
        try {
            $templateId = (int) ($_GET['template_id'] ?? 0);
            if ($templateId <= 0) {
                throw new RuntimeException('يرجى اختيار قالب التقييم.');
            }
            $maxRows = isset($_GET['max_rows']) && $_GET['max_rows'] !== '' ? (int) $_GET['max_rows'] : 100;
            $maxRows = max(1, min($maxRows, 2000));
            $mode = $_GET['mode'] ?? 'prefill';
            $prefillIds = null;
            $pdo = \App\Core\Database::getInstance()->getPdo();
            $stmt = $pdo->prepare('SELECT target_type FROM form_templates WHERE id = ?');
            $stmt->execute([$templateId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $targetType = $row ? $row['target_type'] : 'course';
            if ($mode === 'prefill') {
                if ($targetType === 'course' && !empty($_GET['course_ids'])) {
                    $prefillIds = array_map('intval', explode(',', $_GET['course_ids']));
                    $prefillIds = array_filter($prefillIds, fn($id) => $id > 0);
                    $prefillIds = array_values($prefillIds);
                } elseif ($targetType === 'workshop' && !empty($_GET['workshop_ids'])) {
                    $prefillIds = array_map('intval', explode(',', $_GET['workshop_ids']));
                    $prefillIds = array_filter($prefillIds, fn($id) => $id > 0);
                    $prefillIds = array_values($prefillIds);
                }
            }
            $book = $this->excel->buildTemplate($templateId, $employeeId, null, null, $maxRows, [
                'mode' => $mode,
                'prefill_ids' => $prefillIds,
            ]);
        } catch (RuntimeException $e) {
            Flash::set($e->getMessage(), 'danger');
            $this->redirect('/training/courses');
            return;
        } catch (\Throwable $e) {
            error_log('Training template download error: ' . $e->getMessage());
            Flash::set('تعذر إنشاء القالب. يرجى المحاولة مرة أخرى.', 'danger');
            $this->redirect('/training/courses');
            return;
        }
        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="training-evaluation-template-' . $templateId . '.xlsx"');
        header('X-Content-Type-Options: nosniff');
        (new Xlsx($book))->save('php://output');
        exit;
    }
    public function getTrainingTemplates(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $targetType = $_GET['target_type'] ?? 'course';
        $templates = (new Template())->getActiveByType($targetType);
        echo json_encode($templates);
        exit;
    }
    public function getTrainingList(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $targetType = $_GET['target_type'] ?? 'course';
                $items = (new TrainingProgram())->all($targetType === 'workshop' ? 'workshop' : 'course');
        echo json_encode($items);
        exit;
    }

    /**
     * CD-043 §6: شاشة التصدير — 6 خطوات (القالب، الفرع، القسم، البرنامج، الصفوف، التنزيل).
     */
    public function exportForm(): void
    {
        Auth::enforce('evaluations.export');
        $templates = (new Template())->getAllActiveWithType();
        $branches = (new Branch())->getAllWithDepartments();
        $programs = array_map(static function ($p): array {
            return [
                'id' => (int) $p->id,
                'type' => (string) $p->type,
                'title' => (string) $p->title,
                'executing_branch_id' => (int) ($p->executing_branch_id ?? 0),
                'executing_department_id' => (int) ($p->executing_department_id ?? 0),
            ];
        }, array_merge(
            (new TrainingProgram())->all('course'),
            (new TrainingProgram())->all('workshop')
        ));
        $limits = $this->excel->exportLimits();
        $this->view('evaluations/excel_export', [
            'pageTitle' => 'تصدير قالب Excel',
            'templates' => $templates,
            'branches' => $branches,
            'programs' => $programs,
            'limits' => $limits,
        ]);
    }

    /**
     * CD-043 §6: تنزيل ملف التصدير وفق النطاقات المختارة + تسجيل تدقيق.
     */
    public function downloadExport(): void
    {
        Auth::enforce('evaluations.export');
        $employeeId = $this->currentEmployeeId();
        $limits = $this->excel->exportLimits();
        $templateId = (int) ($_GET['template_id'] ?? 0);
        $filename = 'evaluation-template-' . ($templateId > 0 ? $templateId : 'x') . '-' . date('Ymd-His') . '.xlsx';
        $tmpFile = null;
        try {
            if ($templateId <= 0) {
                throw new RuntimeException('يرجى اختيار قالب التقييم.');
            }
            $template = (new Template())->findById($templateId);
            if (!$template || (int) ($template->is_active ?? 0) !== 1) {
                throw new RuntimeException('القالب المحدد غير موجود أو غير نشط.');
            }
            $targetType = (string) ($template->target_type ?? 'employee');
            if (!in_array($targetType, ['employee', 'employee_in_course', 'employee_in_workshop', 'skill'], true)) {
                throw new RuntimeException('نوع القالب غير مدعوم في CD-043.');
            }
            $branchId = $this->optionalId($_GET['branch_id'] ?? null);
            $departmentId = $this->optionalId($_GET['department_id'] ?? null);
            $isProgram = in_array($targetType, ['employee_in_course', 'employee_in_workshop'], true);
            $programId = $isProgram ? $this->optionalId($_GET['program_id'] ?? null) : null;
            $programScope = $isProgram ? (string) ($_GET['program_scope'] ?? 'all') : 'all';
            if ($programScope !== 'single') { $programId = null; }
            $maxRows = (int) ($_GET['max_rows'] ?? 100);
            $maxRows = max($limits['min_rows'], min($maxRows, $limits['max_rows']));

            $this->assertScopeExists($branchId, $departmentId);
            $options = [
                'mode' => 'prefill',
                'executing_branch_id' => $branchId,
                'executing_department_id' => $departmentId,
            ];
            if ($programId !== null) {
                $options['program_id'] = $programId;
                $options['program_title'] = (string) ($_GET['program_title'] ?? '');
            }
            $book = $this->excel->buildTemplate($templateId, $employeeId, $branchId, $departmentId, $maxRows, $options);

            $tmpFile = tempnam(sys_get_temp_dir(), 'cd043_export_');
            if ($tmpFile === false) {
                throw new RuntimeException('تعذر إنشاء ملف التصدير المؤقت.');
            }
            (new Xlsx($book))->save($tmpFile);
            $size = (int) filesize($tmpFile);
            $maxBytes = $limits['max_file_size_mb'] * 1024 * 1024;
            if ($size > $maxBytes) {
                throw new RuntimeException('حجم الملف الناتج يتجاوز الحد الأقصى (' . $limits['max_file_size_mb'] . ' ميجابايت).');
            }
            Audit::log('EVALUATION_EXCEL_EXPORTED', $templateId, 'form_templates', [
                'template_id' => $templateId,
                'target_type' => $targetType,
                'branch_id' => $branchId,
                'department_id' => $departmentId,
                'program_scope' => $programScope,
                'program_id' => $programId,
                'max_rows' => $maxRows,
                'filename' => $filename,
                'size' => $size,
                'evaluator_employee_id' => $employeeId,
            ]);

            while (ob_get_level() > 0) { ob_end_clean(); }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . $size);
            header('X-Content-Type-Options: nosniff');
            readfile($tmpFile);
            @unlink($tmpFile);
            exit;
        } catch (RuntimeException $e) {
            if ($tmpFile !== null && is_file($tmpFile)) { @unlink($tmpFile); }
            Flash::set($e->getMessage(), 'danger');
            $this->redirect('/evaluations/excel/export');
        } catch (\Throwable $e) {
            if ($tmpFile !== null && is_file($tmpFile)) { @unlink($tmpFile); }
            error_log('Excel export error: ' . $e->getMessage());
            Flash::set('تعذر إنشاء ملف التصدير. يرجى المحاولة مرة أخرى.', 'danger');
            $this->redirect('/evaluations/excel/export');
        }
    }

    private function optionalId($value): ?int
    {
        if ($value === null || $value === '') { return null; }
        $id = (int) $value;
        return $id > 0 ? $id : null;
    }

    private function assertScopeExists(?int $branchId, ?int $departmentId): void
    {
        $pdo = \App\Core\Database::getInstance()->getPdo();
        if ($branchId !== null) {
            $stmt = $pdo->prepare('SELECT id FROM branches WHERE id = :id');
            $stmt->execute(['id' => $branchId]);
            if ($stmt->fetch() === false) { throw new RuntimeException('الفرع المحدد غير موجود.'); }
        }
        if ($departmentId !== null) {
            $stmt = $pdo->prepare('SELECT branch_id FROM departments WHERE id = :id');
            $stmt->execute(['id' => $departmentId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row === false) { throw new RuntimeException('القسم المحدد غير موجود.'); }
            if ($branchId !== null && (int) $row['branch_id'] !== $branchId) {
                throw new RuntimeException('القسم المحدد لا يتبع الفرع المختار.');
            }
        }
    }
}

