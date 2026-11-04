<?php

return [
    'colors' => [
        'primary' => '#0c2823',
        'accent' => '#c8943f',
        'light' => '#f5f0e8',
        'white' => '#ffffff',
        'gray_light' => '#e8e8e8',
        'gray' => '#6c757d',
        'success' => '#0d5e52',
    ],
    'font' => [
        'family' => 'dejavusans',
        'regular' => dirname(__DIR__, 4) . '/vendor/mpdf/mpdf/ttfonts/DejaVuSans.ttf',
        'bold' => dirname(__DIR__, 4) . '/vendor/mpdf/mpdf/ttfonts/DejaVuSans-Bold.ttf',
    ],
    'logo' => dirname(__DIR__, 4) . '/public/assets/images/logo.png',
    'company' => [
        'name' => 'نظام تقييم أداء الموظفين',
        'tagline' => 'مسند رسمي لتقييم الأداء',
    ],
    'confidential' => 'سرّي — للاستخدام الداخلي فقط',
    'footer_text' => 'تم إنشاء هذا المسند آلياً بواسطة نظام تقييم أداء الموظفين',
];
