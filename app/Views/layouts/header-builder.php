<?php
// المسار: app/Views/layouts/header-builder.php
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' . APP_NAME : APP_NAME; ?></title>
      
    <?php /*
     * ===================================================================
     *  ▼▼▼ بداية منطقة التعديل: تحميل ملفات CSS المحلية ▼▼▼
     * ===================================================================
     */ ?>

    <!-- 1. ملف تعريف الخطوط المحلية (لضمان ظهور الخط الصحيح في الباني) -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/fonts.css">
    
    <!-- 2. مكتبة الأيقونات Font Awesome (تم تحويلها إلى محلية) -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/libs/fontawesome/css/all.min.css">
    
    <!-- 3. إطار العمل Bootstrap CSS (تم تحويله إلى محلي) -->
    <link href="<?php echo APP_URL; ?>/public/libs/bootstrap-5.1.3/css/bootstrap.min.css" rel="stylesheet">
    
    <?php /* ▲▲▲ نهاية منطقة التعديل ▲▲▲ */ ?>


    <script>const BASE_URL = "<?php echo APP_URL; ?>";</script>
    <meta name="csrf-token" content="<?php echo \App\Core\Auth::generateCsrfToken(); ?>">

    <!-- ملفات CSS الرئيسية لمشروعك (بدون تغيير) -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/style.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/modern-ui.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/product-ui.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/builder.css">
</head>
<body class="builder-body">
    <!-- لا يوجد أي محتوى مرئي هنا، فقط وسم body المفتوح -->
    <!-- المحتوى الفعلي لصفحة الباني سيتم إدراجه بعد هذا الملف -->
