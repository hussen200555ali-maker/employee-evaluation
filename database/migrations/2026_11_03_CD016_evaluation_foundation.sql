-- CD-016: evaluation metadata and configurable grading scales.
-- Safe to apply repeatedly on MariaDB 10.4+.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE evaluations
    ADD COLUMN IF NOT EXISTS evaluation_type ENUM('annual','periodic','course','workshop') NULL AFTER evaluation_date,
    ADD COLUMN IF NOT EXISTS final_grade_label VARCHAR(50) NULL AFTER final_score,
    ADD COLUMN IF NOT EXISTS final_grade_value DECIMAL(5,2) NULL AFTER final_grade_label;

ALTER TABLE form_templates
    ADD COLUMN IF NOT EXISTS evaluation_mode ENUM('numeric','descriptive','mixed') NOT NULL DEFAULT 'numeric',
    ADD COLUMN IF NOT EXISTS evaluation_frequency ENUM('yearly','quarterly','monthly','ad-hoc') NOT NULL DEFAULT 'yearly';

CREATE TABLE IF NOT EXISTS grading_scales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scale_name VARCHAR(100) NOT NULL,
    grade_label VARCHAR(50) NOT NULL,
    min_value DECIMAL(5,2) NOT NULL,
    max_value DECIMAL(5,2) NOT NULL,
    numeric_value DECIMAL(3,2) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_grading_scale_label (scale_name, grade_label),
    KEY idx_grading_scales_order (scale_name, is_active, sort_order, min_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO grading_scales
    (scale_name, grade_label, min_value, max_value, numeric_value, sort_order, is_active)
VALUES
    ('السلم الخماسي', 'ممتاز', 90.00, 100.00, 5.00, 1, 1),
    ('السلم الخماسي', 'جيد جدًا', 80.00, 89.99, 4.00, 2, 1),
    ('السلم الخماسي', 'جيد', 70.00, 79.99, 3.00, 3, 1),
    ('السلم الخماسي', 'مقبول', 60.00, 69.99, 2.00, 4, 1),
    ('السلم الخماسي', 'ضعيف', 0.00, 59.99, 1.00, 5, 1);

INSERT INTO permissions (name, description, category)
SELECT 'grading_scales.manage', 'إدارة سلم التقديرات', 'الإعدادات'
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE name = 'grading_scales.manage'
);

UPDATE permissions
SET description = 'إدارة سلم التقديرات',
    category = 'الإعدادات'
WHERE name = 'grading_scales.manage';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.name = 'grading_scales.manage'
WHERE r.name = 'Admin' AND r.is_system_role = 1;
