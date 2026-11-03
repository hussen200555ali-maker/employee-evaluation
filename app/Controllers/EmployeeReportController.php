<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Services\ReportExportService;
use App\Services\ReportSnapshotService;
use Throwable;

final class EmployeeReportController extends Controller
{
    private ReportSnapshotService $snapshots;
    private ReportExportService $exports;

    public function __construct()
    {
        $this->snapshots = new ReportSnapshotService();
        $this->exports = new ReportExportService();
    }

    public function generate($employeeId): void
    {
        Auth::enforce('employees.profile');
        $employeeId = (int)$employeeId;
        if (!Auth::validateCsrfToken($_POST['csrf_token'] ?? $_POST['_token'] ?? null)) {
            Flash::set('رمز الحماية غير صالح أو منتهي. يرجى تحديث الصفحة والمحاولة مرة أخرى.', 'danger');
            $this->redirect('/employees/profile/' . max(1, $employeeId));
            return;
        }

        try {
            $snapshot = $this->snapshots->createForEmployee($employeeId, (int)Auth::id());
        } catch (\InvalidArgumentException | \RuntimeException $exception) {
            if ($exception instanceof \PDOException) {
                error_log('Report snapshot database error: ' . $exception->getMessage());
                Flash::set('تعذر حفظ التقرير. يرجى المحاولة مرة أخرى.', 'danger');
            } else {
                Flash::set($exception->getMessage(), 'danger');
            }
            $this->redirect('/employees/profile/' . max(1, $employeeId));
            return;
        } catch (Throwable $exception) {
            error_log('Report snapshot creation error: ' . $exception->getMessage());
            Flash::set('تعذر حفظ التقرير. يرجى المحاولة مرة أخرى.', 'danger');
            $this->redirect('/employees/profile/' . max(1, $employeeId));
            return;
        }

        Flash::set('تم حفظ التقرير الثابت بنجاح.', 'success');
        $this->redirect('/employees/profile/' . $employeeId . '/reports/' . (int)$snapshot->id);
    }

    public function show($employeeId, $snapshotId): void
    {
        Auth::enforce('employees.profile');
        $snapshot = $this->snapshots->findForEmployee((int)$employeeId, (int)$snapshotId);
        if ($snapshot === null) {
            (new ErrorController())->notFound();
            return;
        }

        $this->view('employees/report', [
            'pageTitle' => 'التقرير ' . $snapshot->report_number,
            'employeeId' => (int)$employeeId,
            'snapshot' => $snapshot,
        ]);
    }

    public function export($employeeId, $snapshotId, $format): void
    {
        Auth::enforce('employees.profile');
        if (!in_array($format, ['pdf', 'xlsx', 'docx'], true)) {
            (new ErrorController())->notFound();
            return;
        }
        $snapshot = $this->snapshots->findForEmployee((int)$employeeId, (int)$snapshotId);
        if ($snapshot === null) {
            (new ErrorController())->notFound();
            return;
        }

        try {
            $export = $this->exports->render((string)$format, $snapshot);
        } catch (Throwable $exception) {
            error_log('Report export error: ' . $exception->getMessage());
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'تعذر إنشاء ملف التقرير. يرجى المحاولة مرة أخرى.';
            return;
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $export['content_type']);
        header('Content-Disposition: attachment; filename="' . $export['filename'] . '"');
        header('X-Content-Type-Options: nosniff');
        echo $export['content'];
    }
}
