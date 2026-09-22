<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Models\RatingSetting;

class SettingsController extends Controller {

    public function __construct(){
        Auth::can('settings.manage');
    }

    public function rating(){
        $model=new RatingSetting();
        $settings=$model->getAll();
        $this->view('settings/rating', ['pageTitle'=>'إعدادات التصنيف','settings'=>$settings]);
    }

    public function saveRating(){
        Auth::can('settings.manage');
        if($_SERVER['REQUEST_METHOD']!=='POST') $this->redirect('/settings/rating');
        $labels=$_POST['label']??[];
        $mins=$_POST['min_score']??[];
        $maxs=$_POST['max_score']??[];
        $colors=$_POST['color']??[];
        $rows=[];
        for($i=0;$i<count($labels);$i++){
            $l=trim($labels[$i]??'');
            if($l==='') continue;
            $rows[]=[
                'label'=>$l,
                'min_score'=> (float)($mins[$i]??0),
                'max_score'=> (float)($maxs[$i]??100),
                'color'=> $colors[$i]??'#0d5e52'
            ];
        }
        if(count($rows)<1){
            Flash::set('يجب إضافة فئة واحدة على الأقل','danger');
            $this->redirect('/settings/rating');
        }
        // Validate overlapping
        usort($rows, fn($a,$b)=> $a['min_score'] <=> $b['min_score']);
        foreach($rows as $r){
            if($r['min_score'] > $r['max_score']){
                Flash::set('عتبة غير صحيحة لفئة '.$r['label'],'danger');
                $this->redirect('/settings/rating');
            }
        }
        try{
            (new RatingSetting())->replaceAll($rows);
            Flash::set('تم حفظ إعدادات التصنيف بنجاح - ستظهر فوراً في كل التقارير','success');
        } catch(\Exception $e){
            Flash::set('خطأ: '.$e->getMessage(),'danger');
        }
        $this->redirect('/settings/rating');
    }
}
