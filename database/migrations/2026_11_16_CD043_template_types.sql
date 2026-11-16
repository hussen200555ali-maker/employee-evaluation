-- CD-043 M3: تقليص target_type إلى الأنواع الأربعة المعتمدة
-- المرجع: SRS-EXCEL-IE-3.1-final §5.2/§5.3 + الملحق ب (القرارات 3، 24، 35، 38)
-- الأنواع المعتمدة: employee / employee_in_course / employee_in_workshop / skill
-- ملاحظة: يجب أن يُنفَّذ الفحص المسبق التالي ويعيد 0 صفوف قبل التطبيق:
--   SELECT target_type, COUNT(*) FROM form_templates
--   WHERE target_type NOT IN ('employee','employee_in_course','employee_in_workshop','skill')
--   GROUP BY target_type;
-- النوعان course/workshop (تقييم الدورة/الورشة ككيان) والنوع unit ونوع admin أُلغيت في v3.1.

ALTER TABLE form_templates
    MODIFY COLUMN target_type
        ENUM('employee','employee_in_course','employee_in_workshop','skill')
        NOT NULL DEFAULT 'employee';
