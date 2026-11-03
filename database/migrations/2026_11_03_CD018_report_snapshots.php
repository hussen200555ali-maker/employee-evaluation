<?php
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS report_snapshot_counters (
            id TINYINT UNSIGNED NOT NULL,
            last_number BIGINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB"
    );
    $pdo->exec('INSERT IGNORE INTO report_snapshot_counters (id, last_number) VALUES (1, 0)');

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS report_snapshots (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            report_number VARCHAR(24) NOT NULL,
            employee_id INT NOT NULL,
            created_by_user_id INT NOT NULL,
            snapshot_json JSON NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_report_snapshots_number (report_number),
            KEY idx_report_snapshots_employee (employee_id, id),
            CONSTRAINT fk_report_snapshots_employee
                FOREIGN KEY (employee_id) REFERENCES employees (id) ON DELETE RESTRICT,
            CONSTRAINT fk_report_snapshots_creator
                FOREIGN KEY (created_by_user_id) REFERENCES users (id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $triggerDefinitions = [
        'report_snapshots_block_update' => 'CREATE TRIGGER report_snapshots_block_update
            BEFORE UPDATE ON report_snapshots FOR EACH ROW
            SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'Report snapshots are immutable\'',
        'report_snapshots_block_delete' => 'CREATE TRIGGER report_snapshots_block_delete
            BEFORE DELETE ON report_snapshots FOR EACH ROW
            SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'Report snapshots are immutable\'',
    ];
    $checkTrigger = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.triggers
         WHERE trigger_schema = DATABASE() AND trigger_name = :trigger_name'
    );
    foreach ($triggerDefinitions as $triggerName => $definition) {
        $checkTrigger->execute([':trigger_name' => $triggerName]);
        if ((int)$checkTrigger->fetchColumn() === 0) {
            $pdo->exec($definition);
        }
    }

    echo "CD-018 report_snapshots migration applied successfully.\n";
} catch (PDOException $exception) {
    fwrite(STDERR, 'CD-018 migration failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
