-- CD-043 M2: إعدادات وحدة Excel
-- قابلة للضبط من settings (لا قيم ثابتة في الكود).
-- القرارات المعتمدة: سقف صلب 2000 صف، حد أدنى 50، ملف 50MB، احتفاظ 12/30/365.

INSERT INTO settings (setting_key, setting_value) VALUES
('excel.max_file_size_mb', '50'),
('excel.min_rows', '50'),
('excel.max_rows', '2000'),
('excel.retention_audit_months', '12'),
('excel.retention_rejected_batches_days', '30'),
('excel.retention_batches_days', '365');
