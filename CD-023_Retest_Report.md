تقرير إعادة اختبار CD-023 (الاختبارات فقط) — المساعد المطور
============================================================
التاريخ: 2026-11-06
آخر commit: 6de7092 (origin/master) — يحوي b949178 fix(CD-023): two test-runner defects from assistant report + commit توثيقي تالٍ
بيئة: PHP 8.2.12 (cli) / MariaDB 10.4.32 / employee_evaluation_uat

الالتزام بالقواعد: لم يُعدَّل أي كود. جميع الإجراءات اقتصرت على المزامنة وإعادة التشغيل والإبلاغ فقط.

الاختبارات:
   - الأمر المنفذ: C:\xampp\php\php.exe tests\runner.php
   - النتيجة: 135/136 PASS — 2 فشل
   - الوقت: 18.65 ثانية

تفاصيل الفشل (اثنان، نفس الخطأ):
   1. DescriptiveTemplatesTest::testTextSectionDoesNotAffectFinalScore (السطر 108)
      SQLSTATE[23000]: Integrity constraint violation: 1452
      Cannot add or update a child row: a foreign key constraint fails
      (`employee_evaluation_uat`.`evaluations`,
      CONSTRAINT `eval_fk_creator` FOREIGN KEY (`created_by_user_id`)
      REFERENCES `users` (`id`))
   2. DescriptiveTemplatesTest::testOverallGradeCalculatesCorrectlyForDescriptive (السطر 298)
      SQLSTATE[23000]: Integrity constraint violation: 1452
      Cannot add or update a child row: a foreign key constraint fails
      (`employee_evaluation_uat`.`evaluations`,
      CONSTRAINT `eval_fk_creator` FOREIGN KEY (`created_by_user_id`)
      REFERENCES `users` (`id`))

السبب الجوهري للفشل المتبقي: جدول evaluations يحوي قيد مفتاح خارجية
eval_fk_creator على العمود created_by_user_id المحال إلى users(id). إدراجات
الاختبار الخام في جدول evaluations (makeNumericEvaluationWithSection /
testOverallGradeCalculatesCorrectlyForDescriptive) لا تُزوِّد قيمة
created_by_user_id الصالحة، ما ينتهك القيد. هذا العيب موجود في كود الاختبار
المُسلَّم ولم يُعالَج بإصلاح b949178.

ملاحظات:
   - عيب exit() في BuilderController::__construct تم حله عبر loginAsTemplateManager()
     (اختبارات validateWeights 1-3 تنجح الآن بدون قتل المشغّل).
   - عيب :emp المكرر (HY093) تم حله بفصل المعامل إلى :emp و :entity.
   - 남은 العائق: FK created_by_user_id في إدراجات evaluations.
   - جميع ملفات الاختبار الأخرى (23 ملف، 132 اختبار سابقًا) تنجح بالكامل.
   - UAT بالمتصفح ولقطات الشاشة غير قابلة للتنفيذ في بيئة سطر الأوامر الحالية
     (كما أُبلغ في التقرير الأول).

خلاصة المعيار #4 (136/136 PASS): ✗ — الفعلي 135/136 PASS، فشلان اثنان
في الاختبارين 4 و5 من DescriptiveTemplatesTest بسبب FK created_by_user_id.