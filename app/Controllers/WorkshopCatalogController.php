<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\WorkshopCatalog;
use App\Core\Flash;

class WorkshopCatalogController extends Controller {

    public function __construct() {
        Auth::enforce('training.manage');
    }

    public function index() {
        $model = new WorkshopCatalog();
        $data = [
            'pageTitle' => 'كتالوج الورش',
            'workshops' => $model->getAll()
        ];
        $this->view('training/workshops/catalog/index', $data);
    }

    public function create() {
        $data = ['pageTitle' => 'إضافة ورشة جديدة'];
        $this->view('training/workshops/catalog/create', $data);
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $model = new WorkshopCatalog();
            $data = [
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'category' => $_POST['category'],
                'created_by' => Auth::id()
            ];
            if ($model->create($data)) {
                Flash::set('تمت إضافة الورشة بنجاح ✅', 'success');
            } else {
                Flash::set('حدث خطأ أثناء الإضافة ❌', 'danger');
            }
            $this->redirect('/training/workshops/catalog');
        }
    }

    public function edit($id) {
        $model = new WorkshopCatalog();
        $workshop = $model->find($id);
        if (!$workshop) {
            $this->redirect('/training/workshops/catalog');
            return;
        }
        $data = [
            'pageTitle' => 'تعديل ورشة',
            'workshop' => $workshop
        ];
        $this->view('training/workshops/catalog/edit', $data);
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $model = new WorkshopCatalog();
            $data = [
                'id' => $_POST['id'],
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'category' => $_POST['category']
            ];
            if ($model->update($data)) {
                Flash::set('تم تحديث الورشة ✅', 'success');
            } else {
                Flash::set('حدث خطأ أثناء التحديث ❌', 'danger');
            }
            $this->redirect('/training/workshops/catalog');
        }
    }

  
    // حذف اسم ورشة
public function delete() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $model = new WorkshopCatalog();
        $id = $_POST['id'];

        if ($model->delete($id)) {
            \App\Core\Flash::set('تم حذف الورشة من الكتالوج 🗑️', 'success');
        } else {
            \App\Core\Flash::set('❌ لا يمكن حذف الورشة لأنها مرتبطة بورش منفذة.', 'danger');
        }

        $this->redirect('/training/workshops/catalog');
    }
}

}

