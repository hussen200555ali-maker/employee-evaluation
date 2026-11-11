<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;
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
        // CD-042-PERF: only the default tab's structure is built on page load (≈one
        // template). The remaining tabs are fetched on demand from
        // /evaluations/excel/template-structure and cached client-side.
        $typeOrder = ['employee','skill','employee_in_course','employee_in_workshop','course','workshop','unit'];
        $templatesByType = [];
        foreach ($templates as $tpl) {
            $templatesByType[$tpl->target_type][] = $tpl;
        }
        $defaultTemplateId = 0;
        foreach ($typeOrder as $type) {
            if (!empty($templatesByType[$type])) {
                $defaultTemplateId = (int) $templatesByType[$type][0]->id;
                break;
            }
        }
        $templateStructures = [];
        if ($defaultTemplateId > 0) {
            try {
                $templateStructures[$defaultTemplateId] = $this->excel->describeTemplateStructure($defaultTemplateId, $evaluatorEmployeeId);
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
    /**
     * CD-042-PERF: on-demand template structure for the preview tabs.
     * GET /evaluations/excel/template-structure?template_id={id}
     */
    public function templateStructure(): void
    {
        Auth::enforce('evaluations.fill');
        $templateId = (int) ($_GET['template_id'] ?? 0);
        if ($templateId <= 0) {
            $this->json(['error' => 'معرف القالب غير صالح.'], 400);
            return;
        }
        $pdo = \App\Core\Database::getInstance()->getPdo();
        $stmt = $pdo->prepare('SELECT id FROM form_templates WHERE id = :id AND is_active = 1 LIMIT 1');
        $stmt->execute(['id' => $templateId]);
        if ($stmt->fetchColumn() === false) {
            $this->json(['error' => 'القالب غير موجود أو غير نشط.'], 404);
            return;
        }
        $user = Auth::user();
        $evaluatorEmployeeId = ($user && !empty($user->employee_id)) ? (int) $user->employee_id : 0;
        try {
            $structure = $this->excel->describeTemplateStructure($templateId, $evaluatorEmployeeId);
        } catch (\Throwable $e) {
            error_log('templateStructure error: ' . $e->getMessage());
            $this->json(['error' => 'تعذر تحميل بنية القالب.'], 500);
            return;
        }
        $this->json(['structure' => $structure]);
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
            $book = $this->excel->buildTemplate($templateId, $employeeId, null, null, $maxRows, $mode, $prefillIds);
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
}

