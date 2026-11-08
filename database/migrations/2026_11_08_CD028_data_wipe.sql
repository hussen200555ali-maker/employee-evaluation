-- CD-028: تصفير البيانات التجريبية (Clean Slate)
-- يحذف: التقييمات، الإجابات، المشاركات، القوالب، المعايير، الأقسام
-- يُبقي: المستخدمين، الأدوار، الموظفين، الهيكل التنظيمي، الصلاحيات، report_snapshots (أرشيف)

SET FOREIGN_KEY_CHECKS = 0;

-- 1) تصفير التقييمات والإجابات
TRUNCATE TABLE evaluation_answers;
TRUNCATE TABLE evaluations;

-- 2) تصفير المشاركات
TRUNCATE TABLE course_participants;
TRUNCATE TABLE workshop_participants;

-- 3) تصفير القوالب والمعايير والأقسام
TRUNCATE TABLE criteria_sections;
TRUNCATE TABLE criteria;
TRUNCATE TABLE form_templates;

-- 4) تصفير السلالم (سيعاد إنشاؤها في seed)
TRUNCATE TABLE grading_scales;

-- 5) تصفير تقييمات الدورات/الورش القديمة (System A)
TRUNCATE TABLE course_evaluations;
TRUNCATE TABLE workshop_evaluations;

SET FOREIGN_KEY_CHECKS = 1;

-- التحقق:
-- SELECT COUNT(*) FROM evaluations;        -- المتوقع: 0
-- SELECT COUNT(*) FROM form_templates;     -- المتوقع: 0
-- SELECT COUNT(*) FROM grading_scales;     -- المتوقع: 0
-- SELECT COUNT(*) FROM employees;          -- المتوقع: محفوظ (غير مصفَّر)
-- SELECT COUNT(*) FROM users;              -- المتوقع: محفوظ
