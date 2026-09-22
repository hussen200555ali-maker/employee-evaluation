<?php
namespace App\Core;

use PDO;
use PDOException;

/**
 * ==================================================
 *           ===>  فئة Database المحدّثة  <===
 * ==================================================
 * 
 * تم إضافة ثلاث دوال جديدة في الأسفل للتعامل مع Transactions.
 */
class Database {
    private static $instance = null;
    private $pdo;
    private $stmt;
 private $statement;
    private function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('فشل الاتصال بقاعدة البيانات: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function query($sql) {
        $this->stmt = $this->pdo->prepare($sql);
        return $this;
    }

    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }

    public function execute() {
        return $this->stmt->execute();
    }

    public function fetchAll($fetchStyle = null) {
        $this->execute();
        $style = $fetchStyle ?? $this->pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);
        return $this->stmt->fetchAll($style);
    }

    public function fetch() {
        $this->execute();
        return $this->stmt->fetch();
    }

    public function rowCount() {
        return $this->stmt->rowCount();
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    public function fetchColumn() {
        $this->execute();
        return $this->stmt->fetchColumn();
    }

    // ==================================================
    //           ===>  الدوال الجديدة هنا  <===
    // ==================================================

    /**
     * تبدأ Transaction جديدة.
     * هذه الدالة تستدعي مباشرة دالة beginTransaction من كائن PDO.
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * تقوم بتأكيد (حفظ) التغييرات في الـ Transaction الحالية.
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * تقوم بالتراجع عن كل التغييرات في الـ Transaction الحالية.
     */
    public function rollBack() {
        return $this->pdo->rollBack();
    }
    /**
 * الحصول على كائن PDO مباشرة
 */
public function getPdo() {
    return $this->pdo;
}
}
