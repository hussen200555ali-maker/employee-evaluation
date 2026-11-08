-- CD-028: البيانات الذهبية (Gold Standard) — قوالب + سلالم + دورة + تقييمات
-- يُنفَّذ بعد 2026_11_08_CD028_data_wipe.sql

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1) إعادة إنشاء السلم الخماسي
-- ============================================================
INSERT INTO grading_scales (scale_name, grade_label, min_value, max_value, numeric_value, sort_order, is_active) VALUES
('السلم الخماسي', 'ممتاز',     90.00, 100.00, 5.00, 1, 1),
('السلم الخماسي', 'جيد جداً',  80.00,  89.99, 4.00, 2, 1),
('السلم الخماسي', 'جيد',       70.00,  79.99, 3.00, 3, 1),
('السلم الخماسي', 'مقبول',     60.00,  69.99, 2.00, 4, 1),
('السلم الخماسي', 'ضعيف',       0.00,  59.99, 1.00, 5, 1);

-- ============================================================
-- 2) القالب السنوي (Annual Employee Evaluation)
--    target_type=employee, evaluation_mode=numeric
--    3 معايير: الأداء الوظيفي 40%، السلوك والجدارة 30%، المبادرة 30%
-- ============================================================
INSERT INTO form_templates (template_name, template_description, target_type, evaluation_mode, evaluation_frequency, is_active, version, created_by, updated_by)
VALUES ('التقييم السنوي للموظف', 'تقييم الأداء السنوي الشامل', 'employee', 'numeric', 'yearly', 1, 1, 1, 1);
SET @tpl_annual = LAST_INSERT_ID();

-- معيار 1: الأداء الوظيفي (40%)
INSERT INTO criteria (template_id, criterion_name, criterion_description, criterion_weight, order_index)
VALUES (@tpl_annual, 'الأداء الوظيفي', 'إنجاز المهام والمسؤوليات الأساسية', 40.00, 1);
SET @crit1 = LAST_INSERT_ID();
INSERT INTO criteria_sections (criterion_id, section_name, section_description, evaluation_type, section_weight, min_value, max_value, order_index) VALUES
(@crit1, 'إنجاز الأهداف', 'مدى تحقيق الأهداف المحددة', 'percentage', 60.00, 0, 100, 1),
(@crit1, 'جودة العمل', 'دقة ومتانية المخرجات', 'percentage', 40.00, 0, 100, 2);

-- معيار 2: السلوك والجدارة (30%)
INSERT INTO criteria (template_id, criterion_name, criterion_description, criterion_weight, order_index)
VALUES (@tpl_annual, 'السلوك والجدارة', 'القيم المهنية والتعاون', 30.00, 2);
SET @crit2 = LAST_INSERT_ID();
INSERT INTO criteria_sections (criterion_id, section_name, section_description, evaluation_type, section_weight, min_value, max_value, order_index) VALUES
(@crit2, 'التعاون', 'العمل بروح الفريق', 'percentage', 50.00, 0, 100, 1),
(@crit2, 'التواصل', 'وضوح وفعالية التواصل', 'percentage', 50.00, 0, 100, 2);

-- معيار 3: المبادرة والتطوير (30%)
INSERT INTO criteria (template_id, criterion_name, criterion_description, criterion_weight, order_index)
VALUES (@tpl_annual, 'المبادرة والتطوير', 'الإبداع والسعي للتطور', 30.00, 3);
SET @crit3 = LAST_INSERT_ID();
INSERT INTO criteria_sections (criterion_id, section_name, section_description, evaluation_type, section_weight, min_value, max_value, order_index) VALUES
(@crit3, 'المبادرة', 'الاستباقية في تقديم الحلول', 'percentage', 50.00, 0, 100, 1),
(@crit3, 'التطوير الذاتي', 'السعي لتحسين المهارات', 'percentage', 50.00, 0, 100, 2);

-- ============================================================
-- 3) القالب الوصفي للدورة (Descriptive Course Evaluation)
--    target_type=employee_in_course, evaluation_mode=descriptive
-- ============================================================
INSERT INTO form_templates (template_name, template_description, target_type, evaluation_mode, evaluation_frequency, is_active, version, created_by, updated_by)
VALUES ('التقييم الوصفي للدورة', 'تقييم وصفي لاستفادة الموظف من الدورة', 'employee_in_course', 'descriptive', 'ad-hoc', 1, 1, 1, 1);
SET @tpl_desc_course = LAST_INSERT_ID();

INSERT INTO criteria (template_id, criterion_name, criterion_description, criterion_weight, order_index)
VALUES (@tpl_desc_course, 'تقييم الاستفادة', 'وصف شامل لاستفادة الموظف', 100.00, 1);
SET @crit_dc = LAST_INSERT_ID();
INSERT INTO criteria_sections (criterion_id, section_name, section_description, evaluation_type, section_weight, evaluation_options, order_index) VALUES
(@crit_dc, 'مدى استفادة الموظف', 'وصف تفصيلي لما تعلمه الموظف', 'textarea', 0, '{"max_chars":1000,"required":true}', 1),
(@crit_dc, 'التطبيق العملي', 'كيف سيطبق الموظف ما تعلمه', 'textarea', 0, '{"max_chars":1000,"required":false}', 2),
(@crit_dc, 'التوصيات', 'توصيات للمستقبل', 'textarea', 0, '{"max_chars":500,"required":false}', 3),
(@crit_dc, 'التقدير العام', 'التقدير النهائي للاستفادة', 'overall_grade', 0, '{"scale_name":"السلم الخماسي"}', 4);

-- ============================================================
-- 4) القالب الرقمي للدورة (Numeric Course Evaluation)
--    target_type=employee_in_course, evaluation_mode=numeric
-- ============================================================
INSERT INTO form_templates (template_name, template_description, target_type, evaluation_mode, evaluation_frequency, is_active, version, created_by, updated_by)
VALUES ('التقييم الرقمي للدورة', 'درجة الاختبار النهائي للدورة', 'employee_in_course', 'numeric', 'ad-hoc', 1, 1, 1, 1);
SET @tpl_num_course = LAST_INSERT_ID();

INSERT INTO criteria (template_id, criterion_name, criterion_description, criterion_weight, order_index)
VALUES (@tpl_num_course, 'الاختبار النهائي', 'درجة الاختبار من 100', 100.00, 1);
SET @crit_nc = LAST_INSERT_ID();
INSERT INTO criteria_sections (criterion_id, section_name, section_description, evaluation_type, section_weight, min_value, max_value, order_index) VALUES
(@crit_nc, 'درجة الاختبار', 'الدرجة النهائية من 100', 'numeric', 100.00, 0, 100, 1);

-- ============================================================
-- 5) القالب المهاري (Skill Evaluation)
--    target_type=skill, evaluation_mode=numeric
-- ============================================================
INSERT INTO form_templates (template_name, template_description, target_type, evaluation_mode, evaluation_frequency, is_active, version, created_by, updated_by)
VALUES ('التقييم المهاري', 'تقييم المهارات التقنية والشخصية', 'skill', 'numeric', 'quarterly', 1, 1, 1, 1);
SET @tpl_skill = LAST_INSERT_ID();

-- معيار 1: المهارات التقنية (50%)
INSERT INTO criteria (template_id, criterion_name, criterion_description, criterion_weight, order_index)
VALUES (@tpl_skill, 'المهارات التقنية', 'إتقان الأدوات والتقنيات', 50.00, 1);
SET @crit_s1 = LAST_INSERT_ID();
INSERT INTO criteria_sections (criterion_id, section_name, section_description, evaluation_type, section_weight, min_value, max_value, order_index) VALUES
(@crit_s1, 'إتقان التقنيات', 'مستوى الإتقان', 'percentage', 100.00, 0, 100, 1);

-- معيار 2: مهارات التواصل (25%)
INSERT INTO criteria (template_id, criterion_name, criterion_description, criterion_weight, order_index)
VALUES (@tpl_skill, 'مهارات التواصل', 'العرض والإلقاء', 25.00, 2);
SET @crit_s2 = LAST_INSERT_ID();
INSERT INTO criteria_sections (criterion_id, section_name, section_description, evaluation_type, section_weight, min_value, max_value, order_index) VALUES
(@crit_s2, 'العرض التقديمي', 'جودة العرض', 'percentage', 100.00, 0, 100, 1);

-- معيار 3: حل المشكلات (25%)
INSERT INTO criteria (template_id, criterion_name, criterion_description, criterion_weight, order_index)
VALUES (@tpl_skill, 'حل المشكلات', 'القدرة على التحليل والحل', 25.00, 3);
SET @crit_s3 = LAST_INSERT_ID();
INSERT INTO criteria_sections (criterion_id, section_name, section_description, evaluation_type, section_weight, min_value, max_value, order_index) VALUES
(@crit_s3, 'التحليل', 'القدرة التحليلية', 'percentage', 100.00, 0, 100, 1);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 6) دورة تدريبية + مشاركون (يُنشؤها المساعد عبر الواجهة أو SQL)
-- ============================================================
-- ملاحظة: training_courses تتطلب catalog_id — يجب إنشاء catalog أولاً.
-- المساعد ينشئ: "دورة إدارة المشاريع" + 3 مشاركين عبر الواجهة.

-- التحقق:
-- SELECT id, template_name, target_type, evaluation_mode FROM form_templates;
-- المتوقع: 4 قوالب
-- SELECT * FROM grading_scales WHERE scale_name='السلم الخماسي';
-- المتوقع: 5 تقديرات
