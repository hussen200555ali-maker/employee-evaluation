-- CD-026: البنية التحتية للتدريب + نوع القالب المهاري
-- يحل: G17 (لا course_participants) + إضافة skill للـ target_type

-- ============================================================
-- 1) جدول مشاركة الموظفين في الدورات
-- ============================================================
CREATE TABLE IF NOT EXISTS course_participants (
    id            INT(11) NOT NULL AUTO_INCREMENT,
    course_id     INT(11) NOT NULL COMMENT 'معرف الدورة التدريبية',
    employee_id   INT(11) NOT NULL COMMENT 'معرف الموظف المشارك',
    status        ENUM('pending','completed','absent') NOT NULL DEFAULT 'pending'
                  COMMENT 'حالة المشاركة',
    enrolled_at   TIMESTAMP NOT NULL DEFAULT current_timestamp()
                  COMMENT 'تاريخ التسجيل',
    completed_at  TIMESTAMP NULL DEFAULT NULL COMMENT 'تاريخ الإكمال',
    PRIMARY KEY (id),
    UNIQUE KEY uq_course_employee (course_id, employee_id),
    KEY idx_cp_course (course_id),
    KEY idx_cp_employee (employee_id),
    CONSTRAINT cp_fk_course   FOREIGN KEY (course_id)   REFERENCES training_courses (id) ON DELETE CASCADE,
    CONSTRAINT cp_fk_employee FOREIGN KEY (employee_id) REFERENCES employees (id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='CD-026: يربط الموظف بالدورة التي حضرها (N:M junction)';

-- ============================================================
-- 2) جدول مشاركة الموظفين في الورش
-- ============================================================
CREATE TABLE IF NOT EXISTS workshop_participants (
    id            INT(11) NOT NULL AUTO_INCREMENT,
    workshop_id   INT(11) NOT NULL COMMENT 'معرف الورشة',
    employee_id   INT(11) NOT NULL COMMENT 'معرف الموظف المشارك',
    status        ENUM('pending','completed','absent') NOT NULL DEFAULT 'pending',
    enrolled_at   TIMESTAMP NOT NULL DEFAULT current_timestamp(),
    completed_at  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_workshop_employee (workshop_id, employee_id),
    KEY idx_wp_workshop (workshop_id),
    KEY idx_wp_employee (employee_id),
    CONSTRAINT wp_fk_workshop FOREIGN KEY (workshop_id) REFERENCES training_workshops (id) ON DELETE CASCADE,
    CONSTRAINT wp_fk_employee FOREIGN KEY (employee_id) REFERENCES employees (id)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='CD-026: يربط الموظف بالورشة التي حضرها (N:M junction)';

-- ============================================================
-- 3) إضافة skill إلى target_type في form_templates
-- ============================================================
ALTER TABLE form_templates
  MODIFY COLUMN target_type
  ENUM('employee','employee_in_course','employee_in_workshop',
       'course','workshop','unit','admin','skill')
  NOT NULL DEFAULT 'employee';

-- التحقق:
-- SHOW COLUMNS FROM form_templates LIKE 'target_type';
-- المتوقع: enum يحوي 'skill'
-- SHOW TABLES LIKE '%participants';
-- المتوقع: course_participants + workshop_participants
