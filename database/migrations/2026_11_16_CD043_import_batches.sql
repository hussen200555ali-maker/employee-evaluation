-- CD-043 M4: توسيع evaluate_import_batches لتخزين النطاق والملف الأصلي ودورة life-cycle
-- SRS-EXCEL-IE-3.1-final §16.2/§16.3 + القرارات: تخزين الملف الأصلي خارج public + SHA-256
-- + تتبّع الالتزام/الفشل. فهرسان لدعم الاحتفاظ والتدقيق.

ALTER TABLE evaluation_import_batches
  ADD COLUMN original_file_path VARCHAR(500) NULL COMMENT 'مسار الملف الأصلي خارج public' AFTER original_filename,
  ADD COLUMN file_size BIGINT UNSIGNED NULL COMMENT 'حجم الملف بالبايت' AFTER original_file_path,
  ADD COLUMN file_sha256 CHAR(64) NULL COMMENT 'بصمة SHA-256 للملف الأصلي' AFTER file_size,
  ADD COLUMN scope_json JSON NULL COMMENT 'نطاق التصدير/الاستيراد (فرع/قسم/قالب)' AFTER summary_json,
  ADD COLUMN committing_at DATETIME NULL COMMENT 'بدء الالتزام' AFTER committed_at,
  ADD COLUMN failed_at DATETIME NULL COMMENT 'وقت الفشل' AFTER committing_at,
  ADD COLUMN failure_reason TEXT NULL COMMENT 'سبب الفشل' AFTER failed_at,
  ADD KEY idx_import_batch_status_created (status, created_at),
  ADD KEY idx_import_batch_sha256 (file_sha256);
