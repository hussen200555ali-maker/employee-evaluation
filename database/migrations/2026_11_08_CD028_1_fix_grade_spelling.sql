-- CD-028.1: تصحيح إملائي في السلم الخماسي
-- gold_templates.sql استخدم 'جيد جداً' لكن الاختبار يتوقع 'جيد جدًا'

UPDATE grading_scales
SET grade_label = 'جيد جدًا'
WHERE scale_name = 'السلم الخماسي' AND grade_label = 'جيد جداً';
