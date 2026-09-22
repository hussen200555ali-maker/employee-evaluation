<?php
/**
 * فئة النموذج الأساسية (Base Model)
 * جميع النماذج الأخرى سترث من هذه الفئة.
 */
namespace App\Core;
class Model {
    // متغير لتخزين كائن الاتصال بقاعدة البيانات (PDO).
    protected $db;

    /**
     * دالة البناء (Constructor) التي يتم تشغيلها عند إنشاء نسخة من أي Model.
     */
    public function __construct() {
        // الحصول على نسخة من اتصال قاعدة البيانات وتخزينها.
      
        // ببساطة، نحصل على نسخة من كائن Database ونخزنها.
        $this->db = Database::getInstance();
    }
}
