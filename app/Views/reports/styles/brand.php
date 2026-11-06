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
        'canvas' => '#f5f5f5',
        'zebra' => '#faf8f3',
        'border' => '#e2ded4',
        'ink' => '#1a1a1a',
    ],
    'font' => [
        // mPDF يعتمد أسماء خطوط بأحرف صغيرة فقط.
        'family' => 'cairo',
        // PHPWord يكتب اسم الخط كما هو في مستند Word.
        'word_family' => 'Cairo',
        // نسخ Cairo مهيأة لمحرك OTL في mPDF (بدون MarkGlyphSets).
        // تُولَّد عبر: python public/fonts/prepare-mpdf-fonts.py
        'regular' => dirname(__DIR__, 4) . '/public/fonts/Cairo-Regular-mpdf.ttf',
        'bold' => dirname(__DIR__, 4) . '/public/fonts/Cairo-Bold-mpdf.ttf',
    ],
    'logo' => dirname(__DIR__, 4) . '/public/assets/images/logo.png',
    'company' => [
        'name' => 'نظام تقييم أداء الموظفين',
        'tagline' => 'مسند رسمي لتقييم الأداء',
    ],
    'confidential' => 'سرّي — للاستخدام الداخلي فقط',
    'footer_text' => 'تم إنشاء هذا المسند آلياً بواسطة نظام تقييم أداء الموظفين',
];
