<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Audit;
use App\Models\TrainingCatalog;
use App\Models\TrainingProgram;
use App\Models\ProgramParticipant;
use App\Models\Branch;
use App\Models\Department;

/**
 * CD-036 : TrainingProgramController — unified controller for the Training module
 * (catalog + programs CRUD + registry + details). Framework-matched.
 */
class TrainingProgramController extends Controller {

    public function __construct() {
        // Minimum access for the whole module; mutating methods re-enforce 'training.manage'.
        Auth::enforce('training.view');
    }

    private function requireManage(): void {
        Auth::enforce('training.manage');
    }

    /* =====================  CATALOG (Screen 1)  ===================== */

    public function catalogIndex() {
        $this->view('training_catalogs/index', [
            'pageTitle' => '📚 كتالوج التصنيفات',
            'catalogs'  => (new TrainingCatalog())->all(),
        ]);
    }

    public function catalogStore() {
        $this->requireManage();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('/training/catalogs'); return; }
        $name = trim((string)($_POST['name'] ?? ''));
        $type = $_POST['type'] ?? 'both';
        if ($name === '' || !in_array($type, ['course','workshop','both'], true)) {
            Flash::set('الاسم والنوع مطلوبان.', 'danger');
            $this->redirect('/training/catalogs');
            return;
        }
        $id = (new TrainingCatalog())->create([
            'name'               => $name,
            'type'               => $type,
            'description'        => trim((string)($_POST['description'] ?? '')),
            'category'           => $_POST['category'] ?? null,
            'status'             => (($_POST['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active',
            'created_by_user_id' => Auth::id(),
        ]);
        if ($id) {
            Flash::set('تم حفظ التصنيف بنجاح.', 'success');
            Audit::log('TRAINING_CATALOG_CREATE', $id, 'training_catalogs', ['name' => $name]);
        } else {
            Flash::set('تعذّر حفظ التصنيف.', 'danger');
        }
        $this->redirect('/training/catalogs');
    }

    public function catalogUpdate($id) {
        $this->requireManage();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('/training/catalogs'); return; }
        $name = trim((string)($_POST['name'] ?? ''));
        $type = $_POST['type'] ?? 'both';
        if ($name === '' || !in_array($type, ['course','workshop','both'], true)) {
            Flash::set('الاسم والنوع مطلوبان.', 'danger');
            $this->redirect('/training/catalogs');
            return;
        }
        (new TrainingCatalog())->update((int)$id, [
            'name'        => $name,
            'type'        => $type,
            'description' => trim((string)($_POST['description'] ?? '')),
            'category'    => $_POST['category'] ?? null,
            'status'      => (($_POST['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active',
        ]);
        Flash::set('تم تحديث التصنيف.', 'success');
        $this->redirect('/training/catalogs');
    }

    public function catalogDelete($id) {
        $this->requireManage();
        (new TrainingCatalog())->delete((int)$id);
        Flash::set('تم حذف التصنيف.', 'success');
        $this->redirect('/training/catalogs');
    }

    /* ================  PROGRAM CREATE (Screen 2 & 3)  ================ */

    public function create($type = 'course') {
        $this->requireManage();
        $type = ($type === 'workshop') ? 'workshop' : 'course';
        $this->view('training_programs/create', [
            'type'        => $type,
            'typeLabel'   => $type === 'workshop' ? 'ورشة عمل' : 'دورة تدريبية',
            'typeIcon'    => $type === 'workshop' ? '🏆' : '📚',
            'catalogs'    => (new TrainingCatalog())->byType($type),
            'branches'    => (new Branch())->getAll(),
            'departments' => (new Department())->getAll(),
            'pageTitle'   => ($type === 'workshop' ? '🏆' : '📚') . ' إنشاء ' . ($type === 'workshop' ? 'ورشة عمل' : 'دورة تدريبية'),
        ]);
    }

    public function store() {
        $this->requireManage();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('/training/programs'); return; }
        $type = (($_POST['type'] ?? 'course') === 'workshop') ? 'workshop' : 'course';

        foreach (['title', 'executing_branch_id', 'start_date', 'end_date', 'responsible_person'] as $f) {
            if (trim((string)($_POST[$f] ?? '')) === '') {
                Flash::set('الحقل مطلوب: ' . $f, 'danger');
                $this->redirect('/training/programs/create/' . $type);
                return;
            }
        }

        $id = (new TrainingProgram())->create([
            'type'                    => $type,
            'catalog_id'              => !empty($_POST['catalog_id']) ? (int)$_POST['catalog_id'] : null,
            'title'                   => trim($_POST['title']),
            'description'             => trim((string)($_POST['description'] ?? '')),
            'executing_branch_id'     => (int)$_POST['executing_branch_id'],
            'executing_department_id' => !empty($_POST['executing_department_id']) ? (int)$_POST['executing_department_id'] : null,
            'start_date'              => $_POST['start_date'],
            'end_date'                => $_POST['end_date'],
            'responsible_person'      => trim($_POST['responsible_person']),
            'status'                  => $_POST['status'] ?? 'scheduled',
            'created_by'              => Auth::id(),
        ]);

        if ($id) {
            Flash::set('تم إنشاء البرنامج بنجاح.', 'success');
            Audit::log('TRAINING_PROGRAM_CREATE', $id, 'training_programs', ['type' => $type, 'title' => $_POST['title']]);
            $this->redirect('/training/programs/' . $id);
        } else {
            Flash::set('تعذّر إنشاء البرنامج.', 'danger');
            $this->redirect('/training/programs/create/' . $type);
        }
    }

    /* ================  PROGRAM DETAILS (Screen 4)  ================ */

    public function show($id) {
        $program = new TrainingProgram();
        $p = $program->find((int)$id);
        if (!$p) {
            http_response_code(404);
            echo 'البرنامج غير موجود';
            return;
        }
        $validGrades = ['ممتاز', 'جيد جدًا', 'جيد', 'مقبول'];
        $filters = [
            'search'        => trim((string)($_GET['search'] ?? '')),
            'department_id' => !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null,
            'grade'         => in_array(($_GET['grade'] ?? ''), $validGrades, true) ? $_GET['grade'] : '',
        ];
        $pp = new ProgramParticipant();
        $this->view('training_programs/show', [
            'program'     => $p,
            'stats'       => $program->stats((int)$id),
            'evaluated'   => $pp->evaluatedForProgram((int)$id, $filters),
            'departments' => $pp->departmentsForProgram((int)$id),
            'filters'     => $filters,
            'pageTitle'   => ($p->type === 'workshop' ? '🏆' : '📚') . ' تفاصيل: ' . $p->title,
        ]);
    }

    /** CD-037: JSON list of programs by type (for bulk-create context select). */
    public function apiPrograms($type = 'course') {
        $type = ($type === 'workshop') ? 'workshop' : 'course';
        $this->json((new TrainingProgram())->all($type));
    }

    /* ============ CD-036.3 : Registry (index) + edit/update/destroy ============ */

    public function index() {
        $filters = [
            'search'        => trim((string)($_GET['search'] ?? '')),
            'type'          => in_array(($_GET['type'] ?? ''), ['course','workshop'], true) ? $_GET['type'] : '',
            'branch_id'     => !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : null,
            'department_id' => !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null,
            'from_date'     => trim((string)($_GET['from_date'] ?? '')),
            'to_date'       => trim((string)($_GET['to_date'] ?? '')),
        ];
        $this->view('training_programs/index', [
            'programs'    => (new TrainingProgram())->search($filters),
            'filters'     => $filters,
            'branches'    => (new Branch())->getAll(),
            'departments' => (new Department())->getAll(),
            'pageTitle'   => '📋 سجل البرامج التدريبية',
        ]);
    }

    public function edit($id) {
        $this->requireManage();
        $program = new TrainingProgram();
        $p = $program->find((int)$id);
        if (!$p) {
            http_response_code(404);
            echo 'البرنامج غير موجود';
            return;
        }
        $type = $p->type;
        $this->view('training_programs/create', [
            'type'        => $type,
            'typeLabel'   => $type === 'workshop' ? 'ورشة عمل' : 'دورة تدريبية',
            'typeIcon'    => $type === 'workshop' ? '🏆' : '📚',
            'catalogs'    => (new TrainingCatalog())->byType($type),
            'branches'    => (new Branch())->getAll(),
            'departments' => (new Department())->getAll(),
            'editProgram' => $p,           // prefill signal
            'pageTitle'   => ($type === 'workshop' ? '🏆' : '📚') . ' تعديل: ' . $p->title,
        ]);
    }

    public function update($id) {
        $this->requireManage();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('/training/programs'); return; }
        $type = (($_POST['type'] ?? 'course') === 'workshop') ? 'workshop' : 'course';
        foreach (['title', 'executing_branch_id', 'start_date', 'end_date', 'responsible_person'] as $f) {
            if (trim((string)($_POST[$f] ?? '')) === '') {
                Flash::set('الحقل مطلوب: ' . $f, 'danger');
                $this->redirect('/training/programs/' . $id . '/edit');
                return;
            }
        }
        (new TrainingProgram())->update((int)$id, [
            'type'                    => $type,
            'catalog_id'              => !empty($_POST['catalog_id']) ? (int)$_POST['catalog_id'] : null,
            'title'                   => trim($_POST['title']),
            'description'             => trim((string)($_POST['description'] ?? '')),
            'executing_branch_id'     => (int)$_POST['executing_branch_id'],
            'executing_department_id' => !empty($_POST['executing_department_id']) ? (int)$_POST['executing_department_id'] : null,
            'start_date'              => $_POST['start_date'],
            'end_date'                => $_POST['end_date'],
            'responsible_person'      => trim($_POST['responsible_person']),
            'status'                  => $_POST['status'] ?? 'scheduled',
        ]);
        Flash::set('تم حفظ التعديلات.', 'success');
        $this->redirect('/training/programs/' . $id);
    }

    /** Delete a program — guarded by hasParticipants (CD-036.3). */
    public function destroy($id) {
        $this->requireManage();
        $program = new TrainingProgram();
        if ($program->hasParticipants((int)$id)) {
            Flash::set('لا يمكن حذف البرنامج: لديه مشاركون مقيّمون. أزِل الارتباط أولًا.', 'danger');
            $this->redirect('/training/programs');
            return;
        }
        $program->delete((int)$id);
        Flash::set('تم حذف البرنامج.', 'success');
        $this->redirect('/training/programs');
    }
}
