-- CD-043 M4 rollback: إزالة أعمدة وفهارس توسعة evaluation_import_batches.
-- الترتيب: الفهارس ثم الأعمدة.

ALTER TABLE evaluation_import_batches
  DROP INDEX idx_import_batch_sha256,
  DROP INDEX idx_import_batch_status_created,
  DROP COLUMN failure_reason,
  DROP COLUMN failed_at,
  DROP COLUMN committing_at,
  DROP COLUMN scope_json,
  DROP COLUMN file_sha256,
  DROP COLUMN file_size,
  DROP COLUMN original_file_path;
