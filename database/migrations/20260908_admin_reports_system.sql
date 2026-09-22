-- =============================================
-- هجرة: نظام التقارير الإدارية
-- التاريخ: 2026-09-08
-- الوصف: إضافة دعم التقييم الإداري/التنظيمي
-- =============================================

-- =============================================
-- 1. تعديل ENUM في form_templates (إضافة admin)
-- =============================================
ALTER TABLE form_templates 
  MODIFY target_type ENUM(
    'employee',
    'employee_in_course', 
    'employee_in_workshop',
    'course',
    'workshop',
    'unit',
    'admin'
  ) DEFAULT 'employee';

-- =============================================
-- 2. إضافة حقل الصيغة الحسابية في criteria_sections
-- =============================================
ALTER TABLE criteria_sections 
  ADD COLUMN formula_config JSON NULL 
  COMMENT 'إعدادات الصيغة الحسابية للحقول الإدارية'
  AFTER notes_placeholder;

-- =============================================
-- 3. تعديل ENUM في evaluations (إضافة admin)
-- =============================================
ALTER TABLE evaluations 
  MODIFY entity_type ENUM(
    'employee',
    'course', 
    'workshop',
    'branch',
    'department',
    'admin'
  ) NOT NULL DEFAULT 'employee';

-- =============================================
-- 4. إضافة حقول القوة في evaluations
-- =============================================
ALTER TABLE evaluations 
  ADD COLUMN actual_force INT NULL 
  COMMENT 'القوة الفعلية (عدد الموظفين)' 
  AFTER entity_snapshot,
  ADD COLUMN applied_force INT NULL 
  COMMENT 'القوة المطبقه (المطلوب تطبيقه)' 
  AFTER actual_force,
  ADD COLUMN compliance_rate DECIMAL(5,2) NULL 
  COMMENT 'نسبة المطابقة (محسوب تلقائياً)' 
  AFTER applied_force;

-- =============================================
-- 5. إضافة صلاحيات التقييم الإداري
-- =============================================
INSERT INTO permissions (name, description, category) VALUES
('admin_reports.view', 'عرض التقارير الإدارية', 'تقييم إداري'),
('admin_reports.create', 'إنشاء تقارير إدارية', 'تقييم إداري'),
('admin_reports.edit', 'تعديل تقارير إدارية', 'تقييم إداري'),
('admin_reports.approve', 'اعتماد تقارير إدارية', 'تقييم إداري'),
('admin_reports.archive', 'أرشيف التقارير الإدارية', 'تقييم إداري'),
('admin_templates.manage', 'إدارة قوالب التقييم الإداري', 'تقييم إداري');

-- =============================================
-- 6. تعيين الصلاحيات للدور Admin (id=1)
-- =============================================
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE name LIKE 'admin_%';

-- =============================================
-- 7. جدول المقيّمين للتقارير الإدارية
-- =============================================
CREATE TABLE IF NOT EXISTS admin_report_evaluators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    employee_id INT NOT NULL,
    evaluation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (report_id) REFERENCES evaluations(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY uk_report_evaluator (report_id, employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 8. جدول جدولة التقارير الإدارية
-- =============================================
CREATE TABLE IF NOT EXISTS admin_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    entity_type ENUM('branch','department','organization') NOT NULL,
    entity_id INT NULL,
    title VARCHAR(255) NOT NULL,
    period_type ENUM('daily','weekly','monthly','quarterly','semiannual','annual') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES form_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 9. جدول الإشعارات الإدارية
-- =============================================
CREATE TABLE IF NOT EXISTS admin_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('schedule_deadline','schedule_overdue','report_submitted','report_approved','report_rejected') NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    related_report_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (related_report_id) REFERENCES evaluations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- تم الانتهاء من الهجرة بنجاح
-- =============================================
