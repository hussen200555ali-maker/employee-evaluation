-- تدفقات Excel: دفعة معاينة، صفوف مخزنة، وسجل أخطاء قابل للمراجعة.
CREATE TABLE IF NOT EXISTS evaluation_import_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uploaded_by_user_id INT NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  status ENUM('previewed','committing','completed','failed') NOT NULL DEFAULT 'previewed',
  total_rows INT UNSIGNED NOT NULL DEFAULT 0,
  valid_rows INT UNSIGNED NOT NULL DEFAULT 0,
  invalid_rows INT UNSIGNED NOT NULL DEFAULT 0,
  summary_json LONGTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  committed_at DATETIME NULL,
  INDEX idx_import_batch_user_status (uploaded_by_user_id,status),
  CONSTRAINT fk_import_batch_user FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evaluation_import_rows (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  batch_id BIGINT UNSIGNED NOT NULL,
  source_row_number INT UNSIGNED NOT NULL,
  payload_json LONGTEXT NOT NULL,
  is_valid TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('valid','invalid','committed','skipped') NOT NULL DEFAULT 'valid',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_import_batch_row (batch_id,source_row_number),
  CONSTRAINT fk_import_row_batch FOREIGN KEY (batch_id) REFERENCES evaluation_import_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evaluation_import_row_errors (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  batch_id BIGINT UNSIGNED NOT NULL,
  import_row_id BIGINT UNSIGNED NULL,
  source_row_number INT UNSIGNED NOT NULL,
  error_code VARCHAR(80) NOT NULL,
  error_message VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_import_error_batch (batch_id,source_row_number),
  CONSTRAINT fk_import_error_batch FOREIGN KEY (batch_id) REFERENCES evaluation_import_batches(id) ON DELETE CASCADE,
  CONSTRAINT fk_import_error_row FOREIGN KEY (import_row_id) REFERENCES evaluation_import_rows(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
