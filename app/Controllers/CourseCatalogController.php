<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\CourseCatalog;
use App\Core\Flash;

class CourseCatalogController extends Controller {

    public function __construct() {
        Auth::can('training.manage'); // صلاحية الإدارة
    }

    public function index() {
        $model = new CourseCatalog();
        $data = [
            'pageTitle' => 'كتالوج الدورات',
            'courses' => $model->getAll()
        ];
        $this->view('training/courses/catalog/index', $data);
    }

    public function create() {
        $data = ['pageTitle' => 'إضافة دورة جديدة'];
        $this->view('training/courses/catalog/create', $data);
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $model = new CourseCatalog();
            $data = [
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'category' => $_POST['category'],
                'created_by' => Auth::id()
            ];
            if ($model->create($data)) {
                Flash::set('تمت إضافة الدورة بنجاح ✅', 'success');
            } else {
                Flash::set('تعذر إضافة الدورة ❌', 'danger');
            }
            $this->redirect('/training/courses/catalog');
        }
    }

    public function edit($id) {
        $model = new CourseCatalog();
        $course = $model->find($id);
        if (!$course) {
            $this->redirect('/training/courses/catalog');
            return;
        }
        $data = [
            'pageTitle' => 'تعديل دورة',
            'course' => $course
        ];
        $this->view('training/courses/catalog/edit', $data);
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $model = new CourseCatalog();
            $data = [
                'id' => $_POST['id'],
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'category' => $_POST['category']
            ];
            if ($model->update($data)) {
                Flash::set('تم تحديث الدورة ✅', 'success');
            } else {
                Flash::set('تعذر تحديث الدورة ❌', 'danger');
            }
            $this->redirect('/training/courses/catalog');
        }
    }

    public function delete() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $catalogModel = new \App\Models\CourseCatalog();
        $id = $_POST['id'];

        if ($catalogModel->delete($id)) {
            \App\Core\Flash::set('تم حذف الدورة بنجاح 🗑️', 'success');
        } else {
            \App\Core\Flash::set('لا يمكن حذف الدورة، هناك تقييمات مرتبطة بها ❌', 'danger');
        }

        $this->redirect('/training/courses/catalog');
    }
}

}
