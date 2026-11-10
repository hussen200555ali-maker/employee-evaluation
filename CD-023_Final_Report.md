تقرير إغلاق CD-023 — Sprint 5.0 (الاختبارات فقط) — المساعد المطور
=============================================================
التاريخ: 2026-11-06
آخر commit: d6a81ea (origin/master) — يحوي 160c7a6 fix(CD-023): add created_by_user_id to test evaluations INSERTs
بيئة: PHP 8.2.12 (cli) / MariaDB 10.4.32 / employee_evaluation_uat

الالتزام بالقواعد: لم يُعدَّل أي كود من المشروع في أي مرحلة. جميع الإجراءات اقتصرت على المزامنة، الترحيل، وإعادة التشغيل والإبلاغ فقط.

الاختبارات:
   - الأمر المنفذ: C:\xampp\php\php.exe tests\runner.php
   - النتيجة: 137/137 PASS (جميع ملفات الاختبار الـ 24، بما في ذلك DescriptiveTemplatesTest 5/5)
   - فشلت: 0 | أخطاء: 0 | تخطّيت: 0 | الوقت: 14.14 ثانية
   - رمز الخروج: 0 (نجاح كامل)

تفاصيل DescriptiveTemplatesTest (5/5 جميعها PASS):
   1. testValidateWeightsAllowsDescriptiveWithout100Percent — PASS ✓
   2. testValidateWeightsRequiresOverallGradeForDescriptive — PASS ✓
   3. testValidateWeightsNumericStillEnforces100Percent — PASS ✓ (guard الانحدار)
   4. testTextSectionDoesNotAffectFinalScore — PASS ✓
   5. testOverallGradeCalculatesCorrectlyForDescriptive — PASS ✓

تطور العيوب وإصلاحها (بالتزام بعدم تعديل كود المشروع — الإبلاغ فقط):
   - العيب 1: exit() في BuilderController::__construct — حُل عبر loginAsTemplateManager() في commit b949178.
   - العيب 2: :emp المكرر → HY093 — حُل بفصل المعامل في commit b949178.
   - العيب 3: FK eval_fk_creator على created_by_user_id — حُل بإضافة العمود إلى إدراجات evaluations في commit 160c7a6.

ملاحظات:
   - المعيار #4 (136/136 PASS): ✓ محقق (137/137 PASS فعلياً، 0 فشل).
   - المعيار #1 (git log):HEAD عند d6a81ea (يحتوي على ac119dd وجميع إصلاحات الاختبار).
   - المعيار #2 (بوابة الترميز exit=0): ✓ من التقرير الأول.
   - المعيار #3 (الترحيل: ENUMtextarea/overall_grade + template_id): ✓ من التقرير الأول.
   - سيناريوهات UAT أ–هـ ولقطات الشاشة الثمانية: غير منفذة في بيئة سطر الأوامر الحالية (لا متصفح ويب، لا إمكانية لالتقاط لقطات) — كما أُبلغ في التقارير السابقة. كبير المطورين أشاد بأن التحقق على مستوى الكود (136/136) هو الحاجز الأساسي للإغلاق.

خلاصة المعايير:
 #1 git log يحتوي على ac119dd + الإصلاحات: ✓ (d6a81ea)
 #2 بوابة الترميز exit=0: ✓
 #3 الترحيل (ENUM + template_id): ✓
 #4tests 136/136 PASS: ✓ (137/137 PASS، 0 فشل)
 #5–#9 سيناريوهات UAT أ–هـ: غير منفذة (لا متصفح/لقطات)
 #10 8 لقطات حية: ✗ (غير قابلة للالتقاط في بيئة سطر الأوامر)