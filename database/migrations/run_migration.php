<?php
$host = 'localhost';
$dbname = 'employee_evaluation_mvc';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo 'تم الاتصال بقاعدة البيانات بنجاح' . PHP_EOL;
    
    // 1. تعديل ENUM في form_templates
    $sql1 = "ALTER TABLE form_templates MODIFY target_type ENUM('employee','employee_in_course','employee_in_workshop','course','workshop','unit','admin') DEFAULT 'employee'";
    $pdo->exec($sql1);
    echo '1. تم تعديل form_templates بنجاح' . PHP_EOL;
    
    // 2. إضافة حقل الصيغة
    $sql2 = "ALTER TABLE criteria_sections ADD COLUMN formula_config JSON NULL COMMENT 'إعدادات الصيغة الحسابية' AFTER notes_placeholder";
    $pdo->exec($sql2);
    echo '2. تم إضافة formula_config بنجاح' . PHP_EOL;
    
    // 3. تعديل ENUM في evaluations
    $sql3 = "ALTER TABLE evaluations MODIFY entity_type ENUM('employee','course','workshop','branch','department','admin') NOT NULL DEFAULT 'employee'";
    $pdo->exec($sql3);
    echo '3. تم تعديل evaluations بنجاح' . PHP_EOL;
    
    // 4. إضافة حقول القوة
    $sql4 = "ALTER TABLE evaluations ADD COLUMN actual_force INT NULL COMMENT 'القوة الفعلية' AFTER entity_snapshot, ADD COLUMN applied_force INT NULL COMMENT 'القوة المطبقه' AFTER actual_force, ADD COLUMN compliance_rate DECIMAL(5,2) NULL COMMENT 'نسبة المطابقة' AFTER applied_force";
    $pdo->exec($sql4);
    echo '4. تم إضافة حقول القوة بنجاح' . PHP_EOL;
    
    echo 'تم الانتهاء من تعديلات قاعدة البيانات بنجاح!' . PHP_EOL;
    
} catch(PDOException $e) {
    echo 'خطأ: ' . $e->getMessage() . PHP_EOL;
}
?>