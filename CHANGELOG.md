# سجل التغييرات

## 2026-09-21 — CD-006 المرحلة 1 / بند 6: منع تصعيد الصلاحيات في updatePermission
- `RoleController::updatePermission`: الإلزام بدور Admin (`Auth::isAdmin`) وتضمين دالة جديدة `Role::findPermissionById` + رفض منح صلاحية لا يملكها المنفّذ + Audit بـ`old/new` و`permission_name` و`actor_id`. ترحيل DB غير مطلوب.
- إثبات Live HTTP: Admin يضيف/يزيل (200)، تعديل دور Admin (403)، صلاحية غير موجودة (404)، مستخدم بـ roles.manage دون Admin (403). `php -l` نظيف، وبعد التنظيف DB رجع إلى users=4/user_roles=4/roles=4/permissions=38. نسخ رجوع في `_emergency/rollback/CD-006/2026-09-21/{controllers,models}`.

## 2026-09-21 — CD-006 المرحلة 1 / بند 5: تصحيح صلاحيات Staff (إزالة branches.manage)
- ترحيل `database/migrations/2026_09_21_A2_item5_staff_branches.sql` (+ نسخة التسليم): إزالة `branches.manage` (id14) من Staff (role_id=4) ومنحه `branches.view` (id13)، مع `PERMISSION_REMOVED` في audit_logs ورفع permissions_version (smutairi 2→4).
- إثبات: Staff={branches.view,departments.view} بلا branches.manage، وفحص تطبيقي `User::getUserPermissions(3)` يؤكد ذلك؛ بقية الأدوار دون تغيير.

## 2026-09-21 — CD-006 المرحلة 1 / بند 4: تصحيح صلاحيات Manager (RBAC)
- ترحيل `database/migrations/2026_09_21_A2_item4_manager_rbac.sql` (+ نسخة التسليم): إنشاء `evaluations.view.team` (id39) و`evaluations.create.team` (id40)، ومنح Manager (role_id=3) الصلاحيات الثلاث `evaluations.view.team`/`evaluations.create.team`/`reports.view.team` (id10)، مع `PERMISSION_ADDED` في audit_logs ورفع permissions_version تلقائيًا (kghamdi 2→5).
- إثبات: Manager=3 صلاحيات، وبقية الأدوار دون تغيير، وفحص تطبيقي `User::getUserPermissions(2)` = الثلاث بالضبط. ملاحظة: تمرير stdin عبر PowerShell شوّه العربية → أُعيد التطبيق عبر `mysql source` (UTF-8).

## 2026-09-21 — CD-006 المرحلة 1 / بند 3: حماية آخر مدير نظام نشط
- `User`: `activeAdminCount()`/`isActiveAdmin()`/`isLastActiveAdmin()` بتعريف ديناميكي لدور Admin (`Auth::adminRoleIds`). `UserController`: منع حذف/تعليق/خفض آخر مدير نشط في `destroy`/`toggleStatus`/`updateRole` + `LAST_ADMIN_PROTECTED` + إشعار للمشرفين، ودالة `warnIfSingleActiveAdmin()` تُنبّه عند الهبوط إلى مدير واحد.
- إثبات Live HTTP بجلسة Admin قديمة والمدير النشط الوحيد `sysadmin`: المسارات الثلاثة مُنعت (حذف/خفض 200 مع منع، تعليق 403)، `sysadmin` بقي active/Admin، وإشعارات warning (منع + هبوط إلى 1). تنظيف كامل → users=4. اكتشاف OOS-8 (FK `eval_fk_creator` يسبب 500 عند الحذف).

## 2026-09-21 — CD-006 المرحلة 1 / بند 2: إصلاح شاشة إنشاء المستخدم
- `app/Views/admin/users/create.php` أُعيدت كتابتها من الصفر (كانت 372 سطرًا من markup باني القوالب بترميز مشوّه — اكتشاف CD-005 G1). النموذج الآن: username/email/password/password_confirm/role_id (إلزامي)/employee_id (اختياري) + `csrf_field()` + `POST /admin/users`.
- إثبات Live HTTP بحساب Admin مؤقت: GET الشاشة 200 بكل الحقول والقوائم (أدوار/موظفين) وغياب آثار الباني؛ POST أنشأ مستخدمًا فعليًا (role_id=4) مع `USER_CREATE`. حُذف الحسابان المؤقتان → users=4. النسخة القديمة في `_emergency/rollback/CD-006/2026-09-21/views/`.

## 2026-09-21 — CD-006 المرحلة 1 / بند 1: تنظيف بيانات الاختبار (بقايا CD-004)
- نسخة DB كاملة قبل الحذف: `storage/backups/CD-006/2026-09-21/employee_evaluation_mvc.pre-item1.sql` (1,244,716 بايت، MariaDB dump).
- ترحيل موثق `database/migrations/2026_09_21_A2_item1_cleanup_test_data.sql` (+ نسخة التسليم): حذف **58 حساب اختبار** (`%@test.local`/`test\_%`/`cd004.%`) + **صف عضوية يتيم واحد** في `user_roles`، مع إدراج `USER_CLEANUP_TEST_DATA` في `audit_logs` (id 682/683). ملاحظة: مشغّل `permissions_version` يمنع قراءة `users` داخل حذف `user_roles` (MySQL error 1442) → جدول مؤقت.
- النتيجة: `users=4` (sysadmin/kghamdi/smutairi/fzahrani)، `user_roles=4`، بلا يتيم. إنشاء `CD-006_IMPLEMENTATION.md` + `CD-006_OUT_OF_SCOPE.md`. النسختان (جذر/تسليم) متزامنتان.

## 2026-09-21 — CD-005: توثيق وحدة A2 — المستخدمون والأدوار والصلاحيات
- إذن كبير المستشارين + أمر «ابدأ التنفيذ» 2026-09-21: دراسة/توثيق **قراءة فقط** — لا تعديل كود ولا DB، نطاق A2 فقط (لا A1، لا دين تقني). قراران: (1) شاشة إنشاء المستخدم التالفة تُوثَّق **كاكتشاف فقط** بلا إصلاح → CD-006؛ (2) التسليم = MD + PDF + CHANGELOG في Commit واحد، وADR منفصل لاحقًا فقط عند قرار معماري حقيقي.
- `A2_Users_Roles_Unit_Documentation.md` (جديد، 11 قسمًا + ملحق التوثيق الدائم): نظرة عامة/الملفات والدورة/الشاشات/قواعد العمل/الأمان/القوة (12)/الضعف (16)/الفجوات (G1–G16)/الأسئلة المفتوحة (11)/التوصيات (P1–P3). + `A2_Users_Roles_Unit_Documentation.pdf` (mPDF RTL بخط Cairo عبر سكربت مؤقت خارج كود التطبيق).
- اكتشافات بارزة: **`Views/admin/users/create.php` تالف** (markup باني القوالب بدل نموذج المستخدم — شاشة الإضافة معطوبة، G1 حرجة). **Admin يملك 35/36** ويفتقد `positions.manage` (تعارض مع `02_Org_Structure.md:132`). **HR=2، Manager=0، Staff=`branches.manage`** (خطر أمني). صلاحيات `users.*` معطّلة. لا `Permission` model ولا `/admin/permissions`. لا اختبارات A2. تلوّث 58 حساب اختبار مقابل 4 حقيقية.
- لا تعديل أي ملف تطبيق/قاعدة بيانات. المراجع: `Role.php`, `User.php`, `Auth.php`, `RoleController`, `UserController`, `routes/web.php:78–95`, `main.js:490–790`, `db_live_schema.sql`. النسختان (جذر/تسليم) متزامنتان.

## 2026-09-21 — CD-004 إغلاق رسمي للوحدة A1 (توقيع كبير المستشارين — A1 COMPLETE)
- الإذن 2026-09-21: **فقط إغلاق A1** — تحديث سجلات + Commitment ثم وقف إلزامي. لا بدء لـ CD-005.
- `phase5/A1_Closure_Statement.md` (8 أقسام: ختم رسمي/نطاق/سجل الالتزامات/نتائج القبول 13/13/تقييم/تصريف الملاحظات/الإغلاق/الخطوة التالية). + «§7 اعتماد نهائي» في `Phase5_Acceptance_Report.md` (توقيع المستشار + إدارة المشروع فارغ).
- الاعتماد الكامل: A1 **15/15**، المرحلة 5 **PASS** لا جولة ثانية؛ التقييم: جودة استثنائية، 6 أيام فعلية مقابل 12 مخططة.
- التصريف: S9 + تكرار Notifications → **CD-005**؛ S2 → HTTPS تشغيلي؛ S6 مصفوفة كاملة → **A2**؛ الدين التقني **14 بندًا → CD-005**.
- رأس الحالة في `A1_Authentication_Unit_Documentation.md`/`00_INDEX.md`: لم يُعدَّل (ترميز غير قابل للتعديل الآمن) → سُجّل للتعامل ضمن CD-005. النسختان (جذر/تسليم) متزامنتان. **نقطة التوقف الإلزامية بعد Commit الإغلاق.**

## 2026-09-21 — CD-004 المرحلة 5: اختبار القبول والانحدار (13/13 PASS) — A1 مكتملة
- إذن كبير المستشارين 2026-09-21 (تجهيز + تنفيذ 13 سيناريو + انحدار + تقرير؛ بلا تعديل كود — لم يُعدَّل أي ملف تطبيق في المرحلة 5).
- توثيق التجهيز: `CD-001_delivery/phase5/Phase5_Acceptance_Report.md` + `Phase5_Scenarios.md` (13 سيناريو بخطوات/متوقع/معيار) + `Phase5_Regression_Checklist.md` + `Phase5_Test_Users.md` (6 مستخدمين) + `ROLLBACK_PATHS.md`. نسخة DB كاملة + نسخة كود في `storage/backups/CD-004/phase5/`.
- النتائج 13/13: S1 قفل الحساب، S2 مهلة الجلسة/الكوكي، S3 CSRF الدخول، S4 خروج POST، S5 فحص status، S6 الحارس، S7 إعادة تعيين Admin، S8 تغيير إجباري/حُرّ، S9 سياسة + نبذ آخر 3، S10 انتهاء صلاحية الصلاحيات، S11 رفع تذكّرني، S12 تنظيف cron، S13 دور Admin ديناميكي + إشعارات — التفاصيل والأرقام في التقرير.
- انحدار: AuthTest 58/58، NotificationTest 13/13، فحص صفحات الوحدات عبر HTTP + مواقع forbidden() — بلا انحدار (DatabaseTest 4/5 + HelpersTest 2/3 خط أساس معروف OOS).
- تنظيف كامل لبيانات المرحلة (`temp/phase5_cleanup.sql`): test_* / P5-EMP-* / P5SEED = 0، أدوار ومشغّلات سليمة، DB عادت لحالتها. القرار: A1 مكتملة (15/15) بانتظار التوقيع.

## 2026-09-21 — CD-004 بند 13: cron التنظيف اليومي (cron/cleanup.php)
- إذن مستشار 2026-09-21 بنطاق محدد: (1) `csrf_tokens` المستخدمة أو المنتهية والأقدم من 24 ساعة، (2) `login_attempts` الأقدم من 24 ساعة، (3) الإشعارات `is_read=0` الأقدم من 90 يومًا فقط، (4) تسجيل كل عملية في `audit_logs` (`CRON_CLEANUP_CSRF_TOKENS / _LOGIN_ATTEMPTS / _NOTIFICATIONS` مع count والـrule؛ `user_id=NULL` إجراء نظام). لا «إعادة مصادقة» — مؤجلة (OOS #9 → CD-005).
- حارس CLI + bootstrap بفشل اتصال `exit(1)` وفشل وحدة لا يوقف بقية الوحدات. لا OOS جديد (تحذير المستشار مطابق: الثلاثة فقط؛ أرشفة لاحقة → CD-005).
- اختبار يدوي على DB الحية (بذر 8 صفوف مميزة): قديم يُحذف، حديث (<24 ساعة) يُبقى، إشعار قديم مقروء يُبقى للتدقيق؛ سجل audit بثلاث عمليات ✔؛ تنظيف شامل للبذر/الإدخالات التجريبية. `php -l` نظيف. نسخة `cron-cleanup.pre-item13.php`.

## 2026-09-21 — CD-004 بند 15: شاشة الإشعارات (شيفرة المصدر في جذر المشروع)
- `GET /notifications` → `NotificationController@index` — قائمة حساب (الأحدث أولًا، مقروء + غير مقروء بتمييز)، تعرض الإشعارات **القديمة** (قبل بند 14 — بلا `related_url`) والجديدة، بأنماط enum الأربعة بشارة وتسمية عربية (بلا تعديل ENUM — OOS #1)، وحالة فارغة.
- `POST /notifications/read/:id` (مسار مُسند لصاحب الحساب — لا قراءة لإشعارات الغير) و`POST /notifications/read-all` (لا يمس إشعارات الغير) — POST محمي بـCSRF.
- جرس هيدر: أيقونة + شارة عدد غير المقروء + منسدلة بأحدث 5 إشعارات + «عرض كل الإشعارات»؛ أنماط CSS.
- `app/Models/Notification.php` (جديد، نمط `getPdo+FETCH_ASSOC`): `forUser/unreadCount/recentForUser/markRead/markAllRead` — **بلا أي DELETE**.
- سياسة احتفاظ (تُحترم في cron بند 13): لا يُحذف إلا غير المقروء قبل 90 يومًا (`is_read=0`)، والمقروءة تبقى للتدقيق (متطلب المستشار).
- إثبات: `tests/cases/Core/NotificationTest.php` — **13/13** (عدّاد، ترتيب/legacy، حد الـ5، نطاق per-user، تخزين `notifyAdmins`، عرض كامل، حالة فارغة، بوابة زائر، إجراءا POST). `php -l` نظيف. **المجموعة الكاملة بلا انحدار** (AuthTest 58/58 + خط أساس معروف). نسخ استرجاع `*.pre-item15`.
- اعتماد مشروط متمَّم: شرط «تأكيد session_unset قبل session_regenerate_id (بند 8)» مؤكَّد — ADR في IMPLEMENTATION + `app/Core/Auth.php:498-499`.

## 2026-09-21 — CD-004 بند 8: انعاش نسخة صلاحيات المستخدم (permissions_version) (شيفرة المصدر في جذر المشروع)
- `database/migrations/2026_09_21_A1_permissions_version.sql` (جديد) — **خطوة أولى إلزامية** `UPDATE users SET permissions_version = permissions_version + 1` (أي جلسة نشطة قبل الترحيل تحمل نسخة قديمة) ثم 4 مشغّلات `cd004_trg_*` على `user_roles` و`role_permissions` (AFTER INSERT/DELETE) ترفع نسخة المعنيين عند أي تعديل — قرار المستشار: **Triggers على مستوى البيانات** (الاكتمال > الشفافية). فهرس `user_roles.role_id` متحقق موجود.
- `app/Core/Auth.php` — `permissionsVersionOf()` (‑1 عند تعذر القراءة = لا تغيير، حفاظًا على التوافر)، `refreshSessionPermissions()` (يعيد بناء أدوار/صلاحيات الجلسة ويثبّت النسخة ووقت الفحص — يُستدعى من `login()`)، `permissionsNeedRefresh()` (نافذة كاش `PERMISSIONS_VERSION_CACHE_SECONDS=60`) و`invalidateSessionIfPermissionsChanged()` (حارس الطلب: `session_unset` + `session_regenerate_id(true)` + رسالة «طرأ تعديل…» + `/login`).
- `public/index.php` — استدعاء الحارس بعد `enforceCsrfToken()`.
- `app/Controllers/AuthController.php` — `login()` يستخدم `Auth::refreshSessionPermissions()` (تثبيت النسخة عند الدخول).
- `tests/cases/Core/AuthTest.php` — 7 اختبارات جديدة (من 51 إلى **58**): مشغلا `user_roles`، مشغّل `role_permissions` مع **3 مستخدمين بدور واحد** + مستخدم تحكمي بدور آخر (لو رُفعت مرة واحدة لكان المشغّل خاطئًا)، تثبيت النسخة عند الدخول، إبطال عند الاختلاف، نافذة الـ60 ثانية، جلسة ما قبل بند 8، وانخفاض **Admin→Staff** الإجباري (إبطال + `IS_ADMIN=0` بعد إعادة الدخول).
- إثبات: تطبيق الترحيل على DB الحية (4 مشغّلات + `permissions_version=2` لجميع المستخدمين) + `php -l` نظيف + **AuthTest 58/58** + المجموعة الكاملة بلا انحدار (خط الأساس معروف).
- نسخ تراجع: `_emergency/rollback/CD-004/2026-09-15/Auth.pre-item8.php` + `AuthController.pre-item8.php` + `public/index.pre-item8.php`. تراجع DB: `DROP TRIGGER IF EXISTS cd004_trg_*`.
- **توثيق:** `CD-004_IMPLEMENTATION.md` (بند 8) + `A1_Authentication_Unit_Documentation.md` (§11 صف P2 «انعاش الصلاحيات» → نُفِّذ). التفاصيل في `CD-004_IMPLEMENTATION.md`.

## 2026-09-21 — CD-004 بند 10: شاشة تغيير كلمة المرور (شيفرة المصدر في جذر المشروع)
- `routes/web.php` — `GET/POST /change-password` → `AuthController@showChangePassword/changePassword`.
- `app/Controllers/AuthController.php` — `requireLogin()`؛ `showChangePassword()` (عرض بشـ«مرجع سياسة بند 11»)؛ `changePassword()`: تأكيد كلمة المرور الحالية (`password_verify`) ← مطابقة التأكيد ← `User::updatePassword` (سياسة بند 11 + نبذ آخر 3 + **مسح `must_change_password`**) ← `Audit::log('USER_PASSWORD_CHANGE')` ← `/dashboard`. كل POST محمي بـ CSRF عبر `enforceCsrfToken`.
- `app/Views/auth/change-password.php` (جديد) — نمط صفحة الدخول + حقل الحالية/الجديدة/التأكيد + قائمة مرجع السياسة + `csrf_field()`.
- يكمل بند 9 تلقائيًا (وجهة فرع الإلزام قائمة فعليًا)؛ تدفق تفعيل `invited` خارج النطاق → OOS #10 → CD-005.
- `tests/cases/Core/AuthTest.php` — 8 اختبارات `testChangePassword*`؛ مساعدا `changePasswordScript`/`changePasswordPageScript`.
- إثبات: `php -l` نظيف + **AuthTest 51/51** + المجموعة الكاملة بلا انحدار (خط الأساس معروف؛ DatabaseTest 4/5 + HelpersTest 2/3). خدعتان موثقتان: إخراج الصفحة الكبيرة (~13KB) فوق حد أنبوب Windows يشلّ `runCli` → `$stdoutFile` (OOS #8)؛ و`LOCATION=sent` تحت CLI → تثبيت الوجهات ساكنًا.
- **توثيق:** `CD-004_IMPLEMENTATION.md` (بند 10) + `CD-004_OUT_OF_SCOPE.md` (OOS #10) + `A1_Authentication_Unit_Documentation.md` (§2.3 مسارات، §2.2 المتحكم، §10 سؤالي 2 و7، §11 صف P2). التفاصيل في `CD-004_IMPLEMENTATION.md`.

## 2026-09-21 — CD-004 بند 9: إعادة تعيين كلمة المرور عبر Admin + إلزام التغيير + ر.ق 2 (شيفرة المصدر في جذر المشروع)
- `app/Models/User.php` — `updatePassword($userId, $newPassword, $mustChange = false)`: إعادة التعيين الإدارية تفعّل `must_change_password=1`، والتغيير العادي يمسحه إلى 0 (الحارس/السياسة/التاريخ كما في بند 11).
- `app/Controllers/Admin/UserController.php` — `resetPassword()` يستدعي `updatePassword(..., true)` ورسالة نجاح تُعلم بوجوب التغيير عند الدخول التالي.
- `app/Controllers/AuthController.php` — `login()`: بعد نجاح المصادقة، مستخدم `must_change_password=1` يُوجَّه إجباريًا إلى `/change-password` مع `Flash` توضيحي (الوجهة تُبنى في بند 10).
- **ر.ق 2 (إزالة استعادة كلمة المرور الميتة):** حذف رابط «نسيت كلمة المرور؟» (`href="#"`) + صف `form-options` الفارغ من `login.php`؛ حذف ملف `forgot-password.php` (0 بايت)؛ إزالة `'forgot-password'` من `skipRoutes` في `Auth::enforceCsrfToken` — الإعادة حصرية عبر Admin.
- `tests/cases/Core/AuthTest.php` — 4 اختبارات جديدة + استبدال `testEnforceCsrfTokenSkipsForgotPasswordPost` بـ `testEnforceCsrfTokenNoLongerSkipsForgotPassword`.
- إثبات: `php -l` نظيف + **AuthTest 43/43** + المجموعة الكاملة بلا انحدار (خط الأساس معروف).
- **توثيق:** `CD-004_IMPLEMENTATION.md` (بند 9) + `A1_Authentication_Unit_Documentation.md` (§8-10, G10, §10 سؤال 2, §11 P2 أُجيبوا بالحذف/الإزالة). التفاصيل في `CD-004_IMPLEMENTATION.md`.

## 2026-09-21 — CD-004 بند 11: سياسة كلمة المرور (شيفرة المصدر في جذر المشروع)
- `app/Core/Auth.php` — `PASSWORD_POLICY_MIN_LENGTH=8` + `PASSWORD_POLICY_HISTORY=3` + قائمة `PASSWORD_POLICY_COMMON` (أعلى 100) + `passwordPolicyRules()` + `validatePasswordPolicy()` (الطول/التنوع 3من4/السوداء/نبذ آخر 3) + `passwordReusedRecently()` + `recordPasswordHistory()` (prune إلى 3 عبر derived-table لتجاوز MySQL 1093) + `generateStrongPassword(12)` (CSPRNG يضمن الأنواع الأربعة).
- `app/Models/User.php` — الخاصية `lastPasswordErrors` + الحارس `guardPasswordChange()` + `updatePassword()` و`updateUser()` يفرضان السياسة ويسجّلان هاش القديم في `password_history`.
- `app/Controllers/Admin/UserController.php` — preflight بالسياسة في `store()`/`update()` (Flash برسائل موحّدة) و`resetPassword()` بمولّد `generateStrongPassword` واستجابة أخطاء موحّدة.
- `tests/cases/Core/AuthTest.php` — 9 اختبارات: القواعد المعتمدة، القبول، الرفض (فارغ/قصير/تنوع/سوداء)، نبذ آخر 3 + prune (الرابع الأقدم مسموح)، `updateUser` يفرض السياسة، توافق مولد بقوة مع السياسة بأكملها.
- إثبات: `php -l` نظيف + **AuthTest 40/40** + المجموعة الكاملة بلا انحدار (DatabaseTest 4/5، HelpersTest 2/3 خط أساس معروف).
- **توثيق:** `CD-004_IMPLEMENTATION.md` (بند 11) + `A1_Authentication_Unit_Documentation.md` (§10 سؤال 1 أُجيب + §11 توصية P2 + خارطة NIST/MFA→CD-005). التفاصيل في `CD-004_IMPLEMENTATION.md`.

## 2026-09-21 — إغلاق CD-004: اعتماد البند 6 + خطة C/B/A + سطور الإغلاق الإلزامية
- **إغلاق البند 6 (استيضاح المستشار)**: `git log` يُثبت التسلسل 6(`8a72979`)←7(`991e0bb`)←14(`0982b3e`) — البند 6 أُغلق قبل 7 و14؛ لا إعادة عمل. أدلة مُعادة التوكيد: `php -l` نظيف، `config/app.php`+`performance.php` غائبان، `storage/logs/php-error.log`+`.htaccess` حاضرين، grep بلا مراجع.
- **C — تذييل مرجعي تاريخي** بالنص الموحّد «مرجع تاريخي — الكود الفعلي يستخدم بحثًا ديناميكيًا عبر roles.name='Admin' AND is_system_role=1» في 4 مواضع: `CD-004_Execution_Plan.md:58`، `CD-004_IMPLEMENTATION.md:74`، `A1:246`، `docs/Phase_0_Documentation.md:46`.
- **B — إعادة تحقق بند 14 بعد البند 6**: `AuthTest` **31/31** + تكرر Live HTTP (تدوير roles.id حتى 13 → إشعار عبر الدور الجديد Δ+1 → استعادة → تنظيف USERS=0) **PASS** — سُجّل في قسم بند 14.
- **A — سطور الإغلاق الإلزامية** مصطلح «البند X مكتمل — Commit Y — إثبات في القسم Z» أُضيف رجعيًا للبنود 0،1،2،3،4،5،6،7،12،14؛ وأصبح إلزاميًا في كل تقرير قادم.
- **OUT_OF_SCOPE #7** (App.php:52 — غير حاجب → CD-005) + **#9** (نطاق بند 13 = تنظيف CSRF فقط؛ إعادة المصادقة G13 مؤجلة لـA1-3 → CD-005).
- الاستئناف وفق الاعتماد: المرحلة 3 (11→9→10).

## 2026-09-21 — CD-004 بند 14: دور Admin ديناميكي (إزالة الاعتماد الصريح على role_id = 1)
- `app/Core/Auth.php`: `adminRoleIds()` — يستعلم `roles.name='Admin' AND is_system_role=1` (كاش ثابت لكل طلب) مع Fallback آمن (لا إشعارات + `error_log('CD004_ITEM14…')`) و`clearAdminRoleIdsCache()`؛ `notifyAdmins()` أعيدت كتابتها بـ `role_id IN (:rid…)` وتعود فورًا عند `[]`.
- `app/Core/CsrfGraceLogger.php`: `notifyAdmins` تستخدم `Auth::adminRoleIds()` بنفس النمط.
- `_emergency/create_api_keys_table.sql` + `_emergency/csrf_migration.sql`: `role_id=1` → `IN (SELECT id FROM roles WHERE name='Admin' AND is_system_role=1)` (أدوار HR/Manager/Staff ثابتة كما كانت).
- `tests/cases/Core/AuthTest.php`: `seedAdminUser` ديناميكي + أدوات `rotateAdminRole`/`restoreAdminRole` و`demoteSystemAdminRole`/`restoreSystemAdminRole` و`notificationTitleCount`.
- الإثبات: grep شامل صفر + `php -l` نظيف + `AuthTest` 31/31 (اختبارا الإشعار عبر معرّف جديد وfallback) + Live HTTP (إشعار الموقوف عبر الدور الجديد بعد تدوير roles.id، استعادة، تنظيف USERS=0) **PASS**.
- نسخ تراجع: `_emergency/rollback/CD-004/2026-09-15/Auth.pre-item14.php` + `CsrfGraceLogger.pre-item14.php`.

## 2026-09-16 — CD-004 بند 7: إصلاح حارس Admin/UserController
- `app/Controllers/Admin/UserController.php`: إضافة `exit;` بعد `forbidden()` في `__construct` — سابقًا كان `return forbidden()` بلا `exit` فيستمر تنفيذ دالة الإدارة المستهدفة للمستخدم غير Admin.
- `tests/cases/Core/AuthTest.php`: `testAdminUserControllerGuardStopsNonAdmin` (عمليتان فرعيتان: غير-Admin يتوقف عند 403 بلا محتوى إداري، وAdmin يمر عبر الحارس).
- الإثبات: `php -l` نظيف + `AuthTest` 29/29 + Live HTTP (Staff → GET 403 بلا صفحة إدارية وPOST update-role 403 بلا سجل USER_ROLE_UPDATE، Admin → GET 200 مع الصفحة، تنظيف USERS=0) PASS.
- OUT_OF_SCOPE بند 8: قياس `runCli` (قراءة stderr قبل stdout تسدّ أنبوبة mخرجات HTML الكبيرة) — عولج داخل الاختبار بملف مخرجات.

## 2026-09-16 — CD-004 بند 6: إخفاء الأخطاء ودمج الإعدادات
- `config/config.php`: دمج `app.php` + `performance.php` (مصدر الحقيقة الواحد وفق جدول القيم المعتمد) — `memory_limit=2048M`، `max_execution_time=600`، `BACKUP_BATCH_SIZE=100`؛ `DEBUG_MODE=false` + `display_errors=0` + `log_errors=1` + `error_log → storage/logs/php-error.log`؛ `opcache_reset()` مستبعد عمدًا.
- حذف `config/app.php` + `config/performance.php` نهائيًا (لا `require` لهما — متحقق قبل/بعد).
- `public/index.php`: إزالة فرع `DEBUG_MODE` وطباعة الـ Trace (كانت فقط 44–49) → تسجيل `[FATAL]` كامل في الملف + `http_response_code(500)` + صفحة عامة.
- `storage/logs/` + `storage/.htaccess` (منع الوصول المباشر للسجلات والنسخ عبر الويب).
- `_emergency/verify_phase1.php`: قسم [2/8] لفحوصات الدمج/الإخفاء + `PHP_BINARY` بدل `php`.
- إثبات: `php -l` نظيف + grep بلا مراجع + `AuthTest` 28/28 + اختبار خطأ متعمد (CLI/HTTP) سُجّل في `storage/logs/` ولم يُعرض (PASS) + Live HTTP (login 200، storage 403، مسبار 500 صامت) + verify_phase1 قسم 2 10/10.

## 2026-09-16 — CD-004 بند 4: الخروج عبر POST
- `routes/web.php`: حذف `GET /logout` نهائيًا (أصبح 404) واستبداله بـ`POST /logout` — يمنع Logout-CSRF عبر رابط مُضمَّن.
- `app/Controllers/AuthController.php`: `logout()` — `Auth::logout()` + `session_destroy()` + إتلاف كوكي الجلسة (EVALSESSID) صراحةً عبر `setcookie(session_name(), '', time()-42000, ...)` ثم توجيه `/login`.
- `app/Views/layouts/header.php`: ربط الخروج تحوّل من `<a href>` إلى **نموذج POST + `csrf_field()`** (يُغطَّى بـ`enforceCsrfToken`).
- `tests/cases/Core/AuthTest.php`: `testLogoutRouteIsPostOnly` (تحقق Reflection من خريطة `$router`).
- `CD-001_delivery/appendices/routes_inventory.txt`: سطر logout → POST.
- إثبات: `php -l` نظيف + `AuthTest` 28/28 + Live HTTP (GET /logout→404 والجلسة سليمة؛ POST بلا توكن→رفض CSRF والجلسة سليمة؛ POST بتوكن→302 /login مع كوكي مبطل + الجلسة ملغاة؛ نموذج header يحوي action/token) PASS — تنفيذ لأمر كبير المستشارين (بند 4 المتبقي الحرج في المرحلة 1 ثم المرحلة 2).

## 2026-09-15 — CD-004 بند 1: فحص users.status بعد التحقق من كلمة المرور
- `app/Controllers/AuthController.php`: بعد نجاح `password_verify` — `active` يكمل الدخول؛ `suspended`/`invited` → رفض + `Audit` (`USER_LOGIN_SUSPENDED`/`USER_LOGIN_INVITED`) + إشعار Admin عبر `Auth::notifyAdmins` + فلاش + توجيه `/login` (تدفق `invited` الكامل مع بند 10).
- `tests/cases/Core/AuthTest.php`: 3 اختبارات (رفض suspended/رفض invited/قبول active) عبر عملية فرعية مع بذر مستخدمين مؤقتين وتنظيف تلقائي في `tearDown`.
- إثبات: `php -l` نظيف + `AuthTest` 27/27 (مرتين متتاليتين بلا بقايا) + Live HTTP (suspended/invited→/login مع Audit+إشعار؛ active→/dashboard) PASS.

## 2026-09-15 — CD-004 بند 2: قفل محاولات الدخول
- `app/Core/Auth.php`: ثوابت ومساعدات القفل — `countLoginFailures`, `isLoginLocked`, `logLoginAttempt`, `clearLoginAttempts`, `loginDelaySeconds` (1/2/4/8ث)، `notifyLoginLocked` (إشعار `warning` لـAdmin).
- `app/Controllers/AuthController.php`: منع دخول الحساب المقفول قبل التحقق؛ تسجيل الفشل مع تأخير متدرج قبل الرد؛ قفل عند 5 فاشلة/15د مع `USER_LOGIN_LOCKED` + إشعار؛ نجاح → مسح المحاولات + تسجيل ناجح.
- `tests/cases/Core/AuthTest.php`: 3 اختبارات جديدة (التأخير، العد/القفل/المسح، مسح عند النجاح).
- إثبات: `php -l` نظيف + `AuthTest` 24/24 + Live HTTP (failCount 1→5، أوقات 1/2/4/8/8ث، إشعار للمشرف، الطلبان #6/#7 مُحصَّلين — PASS).

## 2026-09-15 — CD-004 بند 12: إزالة «تذكرني»
- `app/Views/auth/login.php`: حذف checkbox «تذكرني» (كان بلا `name`)؛ بقيت `form-options` مع رابط «نسيت كلمة المرور؟».
- إثبات: `php -l` نظيف + Live HTTP (لا `name="remember"` في صفحة الدخول، رابط forgot-password حاضر).

## 2026-09-15 — CD-004 بند 3: CSRF صفحة الدخول
- `app/Core/Auth.php`: `generateCsrfToken`/`validateCsrfToken` يعملان **قبل المصادقة** (التحقق من الجلسة أولًا؛ DB طبقة ثانية للمصادقين فقط عبر `self::check()`)؛ إزالة `'login'` من `$skipRoutes` في `enforceCsrfToken` → POST /login أصبح يخضع لفحص CSRF.
- `app/Views/auth/login.php`: `csrf_field()` داخل نموذج POST الدخول.
- `tests/cases/Core/AuthTest.php`: استبدال اختبار السلوك القديم (`SkipsLoginPost` — كان يسبب `exit()` في `enforceCsrfToken` فيُخرج مشغّل الاختبارات بصمت) باختبارَي السلوك الجديد (توكن صحيح يمرّ + رفض بلا توكن عبر عملية فرعية).
- إثبات: `php -l` نظيف + محاكاة توكن ما قبل المصادقة PASS + Live HTTP (GET يحوي التوكن؛ إعادة استخدام عبر إعادة التحميل؛ POST بتوكن صحيح → /login، بلا/خاطئ → reject) + `AuthTest` 21/21.

## 2026-09-15 — CD-004 بند 5: تحصين الجلسة
- `config/config.php`: كتلة إعدادات جلسة موحّدة قبل `session_start()` — `session_name('EVALSESSID')`، `gc_maxlifetime=1800`، `cookie_httponly=1`، `cookie_samesite=Strict`، `use_strict_mode=1`، `use_only_cookies=1`، `cookie_lifetime=0`.
- `public/index.php`: تحميل `config/config.php` قبل `session_start()` (كان بعده فيُبطل الإعدادات)؛ **تنبيه**: إعادة تسجيل دخول جميع المستخدمين (اسم جلسة جديد).
- تنظيفات لازمة: إزالة السطر الفارغ الفاتح في `config/config.php` (منع إخراجًا مبكرًا)، وتحويل `public/index.php` من CP-1256 إلى UTF-8 (تحويل معكوس 1:1، نسخة أصلية محفوظة).
- إثبات: `php -l` نظيف + smoke (EVALSESSID/1800/Strict فعّالة) + `AuthTest` 20/20.

## 2026-09-15 — CD-004 المرحلة 0: ترحيل قاعدة بيانات A1
- `database/migrations/2026_09_15_A1_authentication.sql` — إنشاء `login_attempts` (قفل المحاولات) و`password_history` (FK → users) + إضافة `users.must_change_password` و`users.permissions_version` + إزالة `password_resets` (متحقق: 0 صفوف/لا FK/لا مراجع كود).
- اعتماد خطة `CD-004_Execution_Plan.md` (قرار كبير المستشارين 2026-09-15 مع 5 ملاحظات إلزامية)؛ Dry Run لخطة التراجع قبل المرحلة 0 موثقًا.
- نسخ احتياطي قبل الترحيل: `storage/backups/CD-004/2026-09-15/` (mysqldump 884KB) + `_emergency/rollback/CD-004/2026-09-15/` (configs + forgot-password).
- انحدار خفيف بعد الترحيل: `AuthTest` 20/20 نجاح؛ فشلان سابقان خارج النطاق (`DatabaseTest::testRowCount`, `HelpersTest::testConvertToHijriValidDate`) سُجّلا في `CD-004_OUT_OF_SCOPE.md`.

## 2026-09-15 — CD-003: توثيق الوحدة A1 (المصادقة والدخول والجلسات)
- `CD-001_delivery/A1_Authentication_Unit_Documentation.md` + `.pdf` — دراسة قراءة فقط (11 قسمًا) مع مراجع ملف:سطر وجداول قاعدة البيانات.
- خلاصة النتائج: لا قفل محاولات، لا فحص لحالة الحساب عند الدخول، POST /login معفى من CSRF، خروج عبر GET، جلسة غير محصّنة (`config/performance.php` غير محمّل)، حارس `Admin\UserController` مكسور.
- إعلان: لقطات الشاشات غير متوفرة (MySQL متوقف لاستعادة حالة الاستضافة الأصلية).

## 2026-09-02 — حقول المقيّم الحرة وفصل الأدوار
- `evaluations` — `evaluator_employee_id NULLABLE` + `evaluator_name/title VARCHAR(255) NULL` — `created_by_user_id` هو المستورد — تعبئة رجعية من `employees` — `docs/MONTHLY_PRACTICAL_TEMPLATE.md` محدث
- `EvaluationExcelService` — `buildTemplate` لا يملأ `Q/R` تلقائياً (حقلان حران) — `validateSingleRow` قاعدة `evaluator_name أو evaluator_title` أحدهما إلزامي + فحص `^[=+@-]` وطول 255 — `parseFlexibleDate` يقبل `DD-MM-YYYY` — `commitBatch` يحفظ `evaluator_name/title` لكل صف ويحاول مطابقة موظف داخلي وإلا `NULL`
- `EvaluationController::store` — يدعم `is_external_evaluator` مع حقلين حرّين — `Evaluation::create` محدث — `show/print/fill` و `getFullEvaluationData` و `getEvaluationsByEmployeeForYear` تستخدم `COALESCE(evaluator_name, emp.full_name)`
- `create.php` + `evaluation-creator.js` — checkbox مقيّم خارجي مع إظهار/إخفاء الحقلين والتحقق
- `excel_import` — فلترة الأقسام ديناميكية `__lookup` و `max_rows` مرن (سابقا)

## 2026-09-01 — مولد Excel الديناميكي المرن + فلترة الأقسام + مرونة التاريخ
- `buildTemplate` مع `__lookup` و `INDIRECT("Dept_"&S6)` — `maxRows` مرن 100/1000 — `parseFlexibleDate` — `DataValidation` تاريخ غير معيق

## 2026-09-01 — مولد Excel الديناميكي (100 موظف) + قالب التقرير الشهري
- انشاء قالب `id=2` التقرير الشهري — 3 معايير 40/30/30 — 9 اقسام تقدير
## 2026-09-02 � ��� ������ ������� ������
- evaluations_by_year.php � ����� ���� �� 4 KPIs (�����/����/���/����) � grid 3 ����� �� ���� ���� � progress � ����� ����� PHP ����� numeric � clamp 0-100 � ������ navy/gold ������� �� ������ � Evaluation.php:getComparisonStatsForYear ���� ���� status completed
## 2026-09-02 � ����� �������� ������ ��� �������
- evaluations_by_year.php � ������� ����� width:min(96%,1400px) + padding:clamp � ������� uto-fit minmax(240px/340px) � ������ clamp � ����� �� 100% ��� 1366/1920 � 90%/125% zoom � ������� ������� �����
