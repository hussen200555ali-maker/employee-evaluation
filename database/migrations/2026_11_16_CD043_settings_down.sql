-- CD-043 M2 rollback: إزالة إعدادات وحدة Excel الستة.

DELETE FROM settings WHERE setting_key IN (
  'excel.max_file_size_mb',
  'excel.min_rows',
  'excel.max_rows',
  'excel.retention_audit_months',
  'excel.retention_rejected_batches_days',
  'excel.retention_batches_days'
);
