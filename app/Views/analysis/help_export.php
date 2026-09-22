<?php
// ملف: app/Views/analysis/help_export.php
// صفحة مساعدة المستخدمين لفهم كيفية استخدام ميزات تصدير التقارير
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كيفية تصدير التقارير - نظام تقييم الموظفين</title>

    <!-- تضمين ملفات CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/style.css">

    <!-- تضمين Font Awesome للأيقونات -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/libs/fontawesome/css/all.min.css">

    <style>
        .help-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .help-header {
            background-color: #0d6efd;
            color: white;
            padding: 15px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 20px;
        }

        .help-header h1 {
            margin: 0;
            font-size: 24px;
        }

        .help-section {
            margin-bottom: 30px;
        }

        .help-section h2 {
            color: #0d6efd;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #0d6efd;
        }

        .help-section h3 {
            color: #495057;
            margin-bottom: 10px;
            margin-top: 20px;
        }

        .help-section p {
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .help-section ul {
            margin-bottom: 15px;
            padding-right: 20px;
        }

        .help-section li {
            margin-bottom: 8px;
            line-height: 1.6;
        }

        .help-section .note {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }

        .help-section .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }

        .help-section .success {
            background-color: #d1e7dd;
            border-left: 4px solid #198754;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }

        .help-section .step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .step-number {
            background-color: #0d6efd;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
            flex-shrink: 0;
        }

        .step-content {
            flex-grow: 1;
        }

        .help-section img {
            max-width: 100%;
            border-radius: 4px;
            margin: 15px 0;
        }

        .help-section .code {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            margin: 10px 0;
        }

        .help-section .button-example {
            display: inline-block;
            background-color: #0d6efd;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            margin: 5px;
            text-decoration: none;
        }

        .help-section .button-example:hover {
            background-color: #0b5ed7;
        }

        .help-navigation {
            position: fixed;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-height: 80vh;
            overflow-y: auto;
        }

        .help-navigation h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #0d6efd;
        }

        .help-navigation ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .help-navigation li {
            margin-bottom: 5px;
        }

        .help-navigation a {
            color: #495057;
            text-decoration: none;
            font-size: 14px;
            display: block;
            padding: 5px;
            border-radius: 4px;
        }

        .help-navigation a:hover {
            background-color: #e9ecef;
        }

        .help-navigation a.active {
            background-color: #0d6efd;
            color: white;
        }

        @media (max-width: 768px) {
            .help-navigation {
                position: static;
                transform: none;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>

    <!-- حاوية المساعدة -->
    <div class="help-container">
        <!-- ترويسة المساعدة -->
        <div class="help-header">
            <h1><i class="fas fa-question-circle"></i> كيفية تصدير التقارير</h1>
        </div>

        <!-- مقدمة -->
        <div class="help-section">
            <h2>مقدمة</h2>
            <p>يسمح لك نظام تقييم الموظفين بتصدير تقارير الأداء بعدة صيغ مختلفة، بما في ذلك HTML للطباعة المباشرة وPDF للإرسال والتخزين. هذا الدليل سيساعدك على فómoية استخدام هذه الميزات بفعالية.</p>
        </div>

        <!-- خيارات التصدير -->
        <div class="help-section">
            <h2>خيارات التصدير المتاحة</h2>
            <p>عند عرض أي تقرير تحليلي، ستجد عدة خيارات للتصدير في الجزء العلوي من الصفحة:</p>
            <ul>
                <li><strong>طباعة التقرير:</strong> يفتح نافذة طباعة مباشرة من المتصفح</li>
                <li><strong>تصدير PDF:</strong> ينشئ ملف PDF احترافي من التقرير</li>
                <li><strong>طباعة احترافية:</strong> يوفر تقرير PDF بترويسة احترافية</li>
            </ul>
            <div class="note">
                <p><strong>ملاحظة:</strong> جميع خيارات التصدير تدعم اللغة العربية بشكل كامل، مع ضمان ظهور الحروف بشكل صحيح وواضح في الملفات المُصدرة.</p>
            </div>
        </div>

        <!-- كيفية تصدير PDF -->
        <div class="help-section">
            <h2>كيفية تصدير التقرير كملف PDF</h2>

            <h3>الخطوات الأساسية:</h3>
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <p>اذهب إلى صفحة التقرير الذي تريد تصديره</p>
                </div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <p>انقر على زر "تصدير PDF" الموجود في الجزء العلوي من الصفحة</p>
                </div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <p>انتظر حتى يتم إنشاء الملف (قد يستغرق هذا بضع ثوانٍ حسب حجم التقرير)</p>
                </div>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <p>سيتم فتح ملف PDF في نافذة جديدة أو تنزيله حسب إعدادات متصفحك</p>
                </div>
            </div>

            <h3>خيارات متقدمة:</h3>
            <p>يمكنك تخصيص ملف PDF قبل تصديره عبر:</p>
            <ul>
                <li>اختيار حجم الخط والهوامش</li>
                <li>تحديد ما إذا كان تذييل الصفحة يجب أن يتضمن رقم الصفحة</li>
                <li>اختيار ما إذا كان يجب تضمين العلامة المائية "تقرار سري"</li>
            </ul>

            <div class="warning">
                <p><strong>تحذير:</strong> تأكد من استخدام متصفح حديث يدعم HTML5 وCSS3 للحصول على أفضل نتائج عند تصدير التقارير.</p>
            </div>
        </div>

        <!-- طباعة احترافية -->
        <div class="help-section">
            <h2>الطباعة الاحترافية</h2>
            <p>توفر لك خيار "الطباعة الاحترافية" تقرير PDF بمظهر احترافي يشتمل على:</p>
            <ul>
                <li>ترويسة تحتوي على معلومات الشركة وتاريخ التقرير</li>
                <li>شعار الشركة (إذا تم تحميله)</li>
                <li>تذييل الصفحة مع رقم الصفحة</li>
                <li>علامة مائية "تقرار سري" خلف المحتوى</li>
                <li>تنسيق احترافي للجداول والرسوم البيانية</li>
            </ul>

            <div class="success">
                <p><strong>ميزة إضافية:</strong> يمكن تخصيص الترويسة والهامش لتناسب معايير شركتك الرسمية.</p>
            </div>
        </div>

        <!-- نصائح لتحسين التقارير المصدرة -->
        <div class="help-section">
            <h2>نصائح لتحسين التقارير المصدرة</h2>

            <h3>للحصول على أفضل جودة:</h3>
            <ul>
                <li>تأكد من وجود اتصال إنترنت مستقر أثناء تصدير التقارير الكبيرة</li>
                <li>استخدم متصفح Chrome للحصول على أفضل دعم للطباعة</li>
                <li>قم بتعديل إعدادات الطباعة في المتصفح لتحسين المخرجات</li>
            </ul>

            <h3>لتحسين مظهر التقارير:</h3>
            <ul>
                <li>استخدم خطوط عربية واضحة مثل Cairo أو DejaVu</li>
                <li>تجنب استخدام الخطوط المعقدة في الجداول الصغيرة</li>
                <li>استخدم ألوان متناسقة مع هوية شركتك</li>
                <li>استخدم الصور بجودة عالية ولكن بحجم مناسب</li>
            </ul>

            <h3>للحصول على تقارير أكثر احترافية:</h3>
            <ul>
                <li>أضف شعار الشركة في الترويسة</li>
                <li>استخدم هوامش متساوية على جميع جوانب الصفحة</li>
                <li>قم بتضمين ملخص تنفيذي في بداية التقرير</li>
                <li>استخدم أرقام الصفحات والتذييلات المناسبة</li>
            </ul>
        </div>

        <!-- استكشاف الأخطاء وإصلاحها -->
        <div class="help-section">
            <h2>استكشاف الأخطاء وإصلاحها</h2>

            <h3>مشكلة: الملف المصدرة فارغ أو غير مكتمل</h3>
            <p>الحل:</p>
            <ul>
                <li>تحديث الصفحة والمحاولة مرة أخرى</li>
                <li>تأكد من أن التقرير يحتوي على بيانات كافية</li>
                <li>تجنب فتح علامات تبويب متعددة أثناء تصدير التقرير</li>
            </ul>

            <h3>مشكلة: الحروف العربية تظهر بشكل غير واضح في الملف المصدرة</h3>
            <p>الحل:</p>
            <ul>
                <li>تأكد من أن متصفحك يدعم اللغة العربية بشكل كامل</li>
                <li>استخدم خيار "الطباعة الاحترافية" الذي يستخدم خطوطاً معالجة للغة العربية</li>
                <li>تثبيت الخطوط العربية على جهازك إذا كنت تستخدم خيار الطباعة المباشرة</li>
            </ul>

            <h3>مشكلة: حجم الملف المصدرة كبير جداً</h3>
            <p>الحل:</p>
                <ul>
                    <li>تقليل دقة الصور المستخدمة في التقرير</li>
                    <li>تجنب استخدام رسوم بيانية معقدة</li>
                    <li>تقسيم التقرير إلى عدة تقارير أصغر إذا كان يحتوي على كمية كبيرة من البيانات</li>
                </ul>
        </div>

        <!-- الأسئلة الشائعة -->
        <div class="help-section">
            <h2>الأسئلة الشائعة</h2>

            <h3>س: هل يمكنني تصدير تقارير متعددة دفعة واحدة؟</h3>
            <p>ج: حالياً، لا يدعم النظام تصدير تقارير متعددة دفعة واحدة. يجب تصدير كل تقرير على حدة.</p>

            <h3>س: هل يمكنني تخصيص هوية التقرير لتناسب شركتي؟</h3>
            <p>ج: نعم، يمكنك تخصيص شعار الشركة وألوان الترويسة من خلال تحديث إعدادات النظام.</p>

            <h3>س: هل يمكنني إضافة تذييل مخصص لجميع الصفحات؟</h3>
            <p>ج: نعم، خيار "الطباعة الاحترافية" يسمح بتذييل مخصص يتضمن اسم الشركة وتاريخ التقرير ورقم الصفحة.</p>

            <h3>س: هل يمكنني تصدير التقارير بتنسيق Excel أو Word؟</h3>
            <p>ج: حالياً، يدعم النظام تصدير التقارير كملفات HTML للطبقة وPDF فقط. خيارات التصدير الأخرى قد تضاف في الإصدارات القادمة.</p>
        </div>
    </div>

    <!-- قوى التنقل -->
    <div class="help-navigation">
        <h3>التنقل</h3>
        <ul>
            <li><a href="#introduction">مقدمة</a></li>
            <li><a href="#export-options">خيارات التصدير</a></li>
            <li><a href="#export-pdf">كيفية تصدير PDF</a></li>
            <li><a href="#professional-print">الطباعة الاحترافية</a></li>
            <li><a href="#tips">نصائح لتحسين التقارير</a></li>
            <li><a href="#troubleshooting">استكشاف الأخطاء</a></li>
            <li><a href="#faq">الأسئلة الشائعة</a></li>
        </ul>
    </div>

    <!-- تضمين ملف JavaScript -->
    <script>
        // تفعيل التنقل السلس
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('.help-navigation a');

            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();

                    // إزالة الفعالية من جميع الروابط
                    links.forEach(l => l.classList.remove('active'));

                    // إضافة الفعالية للرابط الحالي
                    this.classList.add('active');

                    // التمرير إلى القسم المقابل
                    const targetId = this.getAttribute('href').substring(1);
                    const targetSection = document.getElementById(targetId);

                    if (targetSection) {
                        targetSection.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // تحديث التنقل عند التمرير
            window.addEventListener('scroll', function() {
                const sections = document.querySelectorAll('.help-section');
                const scrollPosition = window.scrollY + 100;

                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.offsetHeight;
                    const sectionId = section.getAttribute('id');

                    if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                        links.forEach(link => {
                            link.classList.remove('active');
                            if (link.getAttribute('href') === '#' + sectionId) {
                                link.classList.add('active');
                            }
                        });
                    }
                });
            });
        });
    </script>

</body>
</html>