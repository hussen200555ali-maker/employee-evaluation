-- CD-026: إضافة الصلاحيات المفقودة من البذور + منحها للأدوار
-- إصلاح P0 من تقرير CD-025: صلاحيات مستخدمة في Auth::enforce لكن غير معرَّفة

-- 1) إدراج الصلاحيات المفقودة (IF NOT EXISTS عبر INSERT IGNORE)
INSERT IGNORE INTO permissions (name, description, category) VALUES
('evaluations.delete', 'حذف التقييمات', 'التقييمات'),
('evaluations.edit', 'تعديل التقييمات (إعادة فتح المسودات)', 'التقييمات'),
('training.evaluate', 'تقييم الدورات والورش', 'التدريب'),
('backup.manage', 'إدارة النسخ الاحتياطي', 'النسخ الاحتياطي'),
('branches.create', 'إنشاء الفروع', 'الهيكل التنظيمي'),
('branches.edit', 'تعديل الفروع', 'الهيكل التنظيمي'),
('branches.delete', 'حذف الفروع', 'الهيكل التنظيمي'),
('departments.create', 'إنشاء الأقسام', 'الهيكل التنظيمي'),
('departments.edit', 'تعديل الأقسام', 'الهيكل التنظيمي'),
('departments.delete', 'حذف الأقسام', 'الهيكل التنظيمي');

-- 2) منح كل الصلاحيات الجديدة لدور Admin (role_id=1)
--    والتحقق من عدم التكرار عبر INSERT IGNORE
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions
WHERE name IN (
    'evaluations.delete', 'evaluations.edit',
    'training.evaluate', 'backup.manage',
    'branches.create', 'branches.edit', 'branches.delete',
    'departments.create', 'departments.edit', 'departments.delete'
);

-- 3) منح training.evaluate لدور HR (role_id=2) أيضاً
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE name = 'training.evaluate';

-- 4) منح branches/departments create/edit/delete لدور HR (role_id=2)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions
WHERE name IN ('branches.create','branches.edit','branches.delete',
               'departments.create','departments.edit','departments.delete');

-- 5) منح evaluations.delete/edit لدور Manager (role_id=3) — لفريقه فقط
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE name IN ('evaluations.delete','evaluations.edit');

-- التحقق:
-- SELECT name FROM permissions WHERE name IN ('evaluations.delete','training.evaluate','backup.manage');
-- المتوقع: 3 صفوف على الأقل
