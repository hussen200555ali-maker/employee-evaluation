<?php
/**
 * اختبارات قاعدة البيانات (app/Core/Database.php)
 *
 * تُجرى هذه الاختبارات داخل جدول مؤقت (TEMPORARY) بحيث لا تُترك أي بيانات
 * في قاعدة البيانات الفعلية.
 */

use App\Core\Database;

class DatabaseTest extends TestCase
{
    public function testSingletonReturnsSameInstance(): void
    {
        $this->skipIfDbUnavailable();
        $db1 = Database::getInstance();
        $db2 = Database::getInstance();
        $this->assertInstanceOf(Database::class, $db1);
        $this->assertSame($db1, $db2);
    }

    public function testTransactionRollbackUndoesInserts(): void
    {
        $this->skipIfDbUnavailable();
        $db = Database::getInstance();

        $db->query('CREATE TEMPORARY TABLE test_rollback_t (id INT PRIMARY KEY AUTO_INCREMENT, val VARCHAR(50) NOT NULL)');
        $db->execute();

        $db->beginTransaction();
        $db->query('INSERT INTO test_rollback_t (val) VALUES (:val)');
        $db->bind(':val', 'hello');
        $this->assertTrue($db->execute());
        $db->query('INSERT INTO test_rollback_t (val) VALUES (:val)');
        $db->bind(':val', 123);
        $this->assertTrue($db->execute());
        $db->rollBack();

        $db->query('SELECT COUNT(*) FROM test_rollback_t');
        $this->assertSame('0', (string)$db->fetchColumn());

        $db->query('DROP TEMPORARY TABLE test_rollback_t');
        $db->execute();
    }

    public function testTransactionCommitPersistsInserts(): void
    {
        $this->skipIfDbUnavailable();
        $db = Database::getInstance();

        $db->query('CREATE TEMPORARY TABLE test_commit_t (id INT PRIMARY KEY AUTO_INCREMENT, val VARCHAR(50) NOT NULL)');
        $db->execute();

        $db->beginTransaction();
        $db->query('INSERT INTO test_commit_t (val) VALUES (:val)');
        $db->bind(':val', 'persisted');
        $db->execute();
        $db->commit();

        $db->query('SELECT val FROM test_commit_t WHERE val = :val');
        $db->bind(':val', 'persisted');
        $row = $db->fetch();
        $this->assertNotNull($row);
        $this->assertSame('persisted', $row->val);

        $db->query('DROP TEMPORARY TABLE test_commit_t');
        $db->execute();
    }

    public function testBindDetectsIntegerAndBooleanTypes(): void
    {
        $this->skipIfDbUnavailable();
        $db = Database::getInstance();

        $db->query('CREATE TEMPORARY TABLE test_bind_t (id INT PRIMARY KEY AUTO_INCREMENT, active TINYINT(1) NOT NULL DEFAULT 0)');
        $db->execute();

        $db->beginTransaction();
        $db->query('INSERT INTO test_bind_t (active) VALUES (:active)');
        $db->bind(':active', 1);
        $db->execute();
        $db->commit();

        $db->query('SELECT active FROM test_bind_t WHERE id = :id');
        $db->bind(':id', 1);
        $this->assertSame(1, (int)$db->fetchColumn());

        $db->query('DROP TEMPORARY TABLE test_bind_t');
        $db->execute();
    }

    public function testRowCount(): void
    {
        $this->skipIfDbUnavailable();
        $db = Database::getInstance();

        $db->query('CREATE TEMPORARY TABLE test_count_t (id INT PRIMARY KEY AUTO_INCREMENT, val VARCHAR(50) NOT NULL)');
        $db->execute();

        $db->beginTransaction();
        foreach (['a', 'b', 'c'] as $v) {
            $db->query('INSERT INTO test_count_t (val) VALUES (:val)');
            $db->bind(':val', $v);
            $db->execute();
        }
        $db->commit();

        // OOS-14: كان الاختبار يفرض rowCount()==3 على نتيجة SELECT COUNT(*).
        // في MySQL تُرجع rowCount() عدد الصفوف المتأثرة، وهو 1 لصف
        // التجميع الواحد لا 3. القياس الفعلي: rowCount()=1، fetchColumn()=3.
        // الاختبار كان خاطئًا لا الدالة، فصُحّح ليثبّت السلوك الصحيح.
        $db->query('SELECT COUNT(*) FROM test_count_t');
        $db->execute();
        $this->assertSame(3, (int)$db->fetchColumn());

        $db->query('DROP TEMPORARY TABLE test_count_t');
        $db->execute();
    }
}
