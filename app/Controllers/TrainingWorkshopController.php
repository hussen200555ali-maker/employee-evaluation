<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\TrainingWorkshop;
use App\Models\Branch;
use App\Models\Department;
use App\Core\Flash;
use App\Models\WorkshopCatalog;
class TrainingWorkshopController extends Controller {

    public function __construct() {
        Auth::can('training.manage');
    }

    public function index() {
        $workshopModel = new TrainingWorkshop();
        $data = [
            'pageTitle' => 'إدارة الورش التدريبية',
            'workshops' => $workshopModel->getAll()
        ];
        $this->view('training/workshops/index', $data);
    }
/*
    public function create() {
        $branchModel = new Branch();
        $data = [
            'pageTitle' => 'إضافة ورشة جديدة',
            'branches' => $branchModel->getAll(),
            'departments' => []
        ];
        $this->view('training/workshops/create', $data);
    }
*/

public function create() {
    $branchModel = new Branch();
    $catalogModel = new WorkshopCatalog();

    $data = [
        'pageTitle' => 'إضافة ورشة جديدة',
        'branches' => $branchModel->getAll(),
        'catalog_workshops' => $catalogModel->getAll(),
        'departments' => []
    ];
    $this->view('training/workshops/create', $data);
}
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $workshopModel = new TrainingWorkshop();
                          $data = [
                    'catalog_id' => $_POST['catalog_id'],  
                    'branch_id' => $_POST['branch_id'],
                    'department_id' => $_POST['department_id'],
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date'],
                    'status' => $_POST['status'],
                    'created_by' => Auth::id()
                              ];

            if ($workshopModel->create($data)) {
                Flash::set('تمت إضافة الورشة بنجاح.', 'success');
            } else {
                Flash::set('حدث خطأ أثناء إضافة الورشة.', 'danger');
            }
            $this->redirect('/training/workshops');
        }
    }

  public function edit($id) {
    $workshopModel = new TrainingWorkshop();
    $branchModel = new Branch();
    $departmentModel = new Department();
    $catalogModel = new WorkshopCatalog();

    $workshop = $workshopModel->findById($id);
    if (!$workshop) {
        $this->redirect('/training/workshops');
        return;
    }

    $data = [
        'pageTitle' => 'تعديل ورشة',
        'workshop' => $workshop,
        'branches' => $branchModel->getAll(),
        'departments' => $departmentModel->getForBranch($workshop->branch_id),
        'catalog_workshops' => $catalogModel->getAll() // ✅ مهم
    ];
    $this->view('training/workshops/edit', $data);
}


  public function update() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $workshopModel = new TrainingWorkshop();
        $data = [
            'id' => $_POST['id'],
            'catalog_id' => $_POST['catalog_id'],
            'branch_id' => $_POST['branch_id'],
            'department_id' => $_POST['department_id'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'status' => $_POST['status']
        ];
        if ($workshopModel->update($data)) {
            Flash::set('تم تحديث الورشة بنجاح ✅', 'success');
        } else {
            Flash::set('لم يتم إجراء أي تعديل ❌', 'warning');
        }
        $this->redirect('/training/workshops');
    }
}

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $workshopModel = new TrainingWorkshop();
            $id = $_POST['id'];
            if ($workshopModel->delete($id)) {
                Flash::set('تم حذف الورشة بنجاح.', 'success');
            } else {
                Flash::set('حدث خطأ أثناء حذف الورشة.', 'danger');
            }
            $this->redirect('/training/workshops');
        }
    }

    
}
