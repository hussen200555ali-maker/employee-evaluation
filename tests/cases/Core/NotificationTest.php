<?php
/**
 * اختبارات بند 15 — شاشة الإشعارات (app/Controllers/NotificationController.php
 * + app/Models/Notification.php + جرس الهيدر).
 */

use App\Core\Auth;
use App\Models\Notification;

class NotificationTest extends TestCase
{
    private const TEST_PASSWORD = 'CorrectP@ss123!';

    /** أسماء مستخدمين مؤقتة بُذرت خلال الاختبارات — تُنظَّف في tearDown */
    private array $seededUsernames = [];

    public function tearDown(): void
    {
        $this->cleanupSeededUsers();
        parent::tearDown();
    }

    // ======================================================================
    // أدوات عامة
    // ======================================================================

    private function testDb(): \PDO
    {
        return new \PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    /** بذر مستخدم فريد (لاحقة عشوائية تمنع تصادم بين أشواط متتابعة). */
    private function seedUser(string $base): int
    {
        $username = $base . '_' . substr(md5(uniqid('', true)), 0, 6);
        $db = $this->testDb();
        $db->prepare('INSERT INTO users (username, email, password_hash, status) VALUES (?, ?, ?, \'active\')')
            ->execute([$username, $username . '@cd004.t15', password_hash(self::TEST_PASSWORD, PASSWORD_DEFAULT)]);
        $id = (int)$db->lastInsertId();
        $this->seededUsernames[] = $username;
        return $id;
    }

    private function seedNotification(int $userId, string $title, int $isRead = 0, string $type = 'info', ?string $relatedUrl = null, string $createdAt = '2026-09-21 10:00:00'): int
    {
        $db = $this->testDb();
        $db->prepare('INSERT INTO notifications (user_id, title, message, type, is_read, related_url, created_at)
                      VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$userId, $title, 'رسالة ' . $title, $type, $isRead, $relatedUrl, $createdAt]);
        return (int)$db->lastInsertId();
    }

    private function isReadOf(int $notificationId): int
    {
        $st = $this->testDb()->prepare('SELECT is_read FROM notifications WHERE id = ?');
        $st->execute([$notificationId]);
        return (int)$st->fetchColumn();
    }

    private function countNotifications(int $userId): int
    {
        $st = $this->testDb()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ?');
        $st->execute([$userId]);
        return (int)$st->fetchColumn();
    }

    private function cleanupSeededUsers(): void
    {
        $usernames = $this->seededUsernames;
        if ($usernames === [] || !db_available()) {
            return;
        }
        try {
            $db = $this->testDb();
            $ph = implode(',', array_fill(0, count($usernames), '?'));
            // جدول مؤقت لتجنّب "users used by statement which invoked trigger" (بند 8:
            // حذف من user_roles يطلق محفّزًا يحدّث users — فلا يُقرأ users في نفس الاستعلام)
            $db->exec('DROP TEMPORARY TABLE IF EXISTS tmp_clean_cd004');
            $db->prepare("CREATE TEMPORARY TABLE tmp_clean_cd004 AS SELECT id FROM users WHERE username IN ({$ph})")->execute($usernames);
            $db->prepare('DELETE n FROM notifications n INNER JOIN tmp_clean_cd004 t ON n.user_id = t.id')->execute();
            $db->prepare('DELETE ur FROM user_roles ur INNER JOIN tmp_clean_cd004 t ON ur.user_id = t.id')->execute();
            $db->exec('SET FOREIGN_KEY_CHECKS = 0');
            try {
                $db->prepare('DELETE u FROM users u INNER JOIN tmp_clean_cd004 t ON u.id = t.id')->execute();
            } finally {
                $db->exec('SET FOREIGN_KEY_CHECKS = 1');
            }
            $db->exec('DROP TEMPORARY TABLE IF EXISTS tmp_clean_cd004');
        } catch (\Throwable $e) {
            // التنظيف ثانوي — لا يُفشِل الاختبار
        }
        $this->seededUsernames = [];
    }

    // ======================================================================
    // سكربتات CLI (عمليات فرعية معزولة — cwd = public كما في AuthTest)
    // ======================================================================

    private function scriptHeader(): string
    {
        $boot = var_export(APP_ROOT . '/tests/bootstrap.php', true);
        // ob_start من البداية: الهيدر (Location) يبقى معلّقًا في CLI فيلتقطه shutdown
        return '<?php
            require ' . $boot . ';
            ob_start();
            register_shutdown_function(function () {
                echo "LOCATION=" . (headers_sent() ? "sent" : implode("|", headers_list())) . PHP_EOL;
            });
        ';
    }

    /** سكربت يعرض شاشة الإشعارات ويطبع HTML + SCREEN_DONE. */
    private function indexScript(int $userId): string
    {
        $script = $this->tempFile('php');
        $uid = var_export($userId, true);
        file_put_contents($script, $this->scriptHeader() . '
            $_SESSION["user_id"] = ' . $uid . ';
            $_SESSION["user_object"] = (object)["username" => "cd004_t15"];
            try {
                (new \App\Controllers\NotificationController())->index();
            } catch (\Throwable $e) {
                echo "ERROR=" . $e->getMessage() . PHP_EOL;
            }
            echo ob_get_clean();
            echo "SCREEN_DONE" . PHP_EOL;
        ');
        return $script;
    }

    /** سكربت تعليم إشعار واحد/الكل كمقروء عبر POST (يُلتقط LOCATION عند exit). */
    private function postActionScript(string $action, int $userId, ?int $notificationId = null): string
    {
        $script = $this->tempFile('php');
        $uid = var_export($userId, true);
        $id = var_export($notificationId, true);
        file_put_contents($script, $this->scriptHeader() . '
            $_SERVER["REQUEST_METHOD"] = "POST";
            $_SESSION["user_id"] = ' . $uid . ';
            $controller = new \App\Controllers\NotificationController();
            try {
                if (' . var_export($action, true) . ' === "one") {
                    $controller->markRead(' . $id . ');
                } else {
                    $controller->markAllRead();
                }
            } catch (\Throwable $e) {
                echo "ERROR=" . $e->getMessage() . PHP_EOL;
            }
            echo "SURVIVED_ACTION" . PHP_EOL;
        ');
        return $script;
    }

    /** سكربت محاولة فتح الشاشة دون جلسة (مسؤولية البوابة). */
    private function guestScript(): string
    {
        $script = $this->tempFile('php');
        file_put_contents($script, $this->scriptHeader() . '
            try {
                (new \App\Controllers\NotificationController())->index();
            } catch (\Throwable $e) {
                echo "ERROR=" . $e->getMessage() . PHP_EOL;
            }
            echo "SURVIVED_GUEST" . PHP_EOL;
        ');
        return $script;
    }

    /** تشغيل سكربت CLI مع ملف stdout (تجنب أنبوب OOS #8) وإعادة الناتج. */
    private function run(string $script): string
    {
        $out = $this->tempFile('txt');
        $r = $this->runCli([$script], $out);
        $stdout = is_file($out) ? file_get_contents($out) : '';
        if ($r['exit'] !== 0 && trim($r['stderr']) !== '') {
            $this->fail('خروج غير صفري (' . $r['exit'] . '): ' . $r['stderr']);
        }
        return $stdout;
    }

    // ======================================================================
    // اختبارات النموذج (بلا HTTP)
    // ======================================================================

    public function testDbUnavailableSkips(): void
    {
        $this->skipIfDbUnavailable('بند 15');
    }

    public function testUnreadCountIsZeroWhenNoNotifications(): void
    {
        $this->skipIfDbUnavailable('بند 15');
        $uid = $this->seedUser('cd004_t15_empty');
        $this->assertSame(0, (new Notification())->unreadCount($uid));
    }

    public function testUnreadCountCountsOnlyUnreadRows(): void
    {
        $this->skipIfDbUnavailable('بند 15');
        $uid = $this->seedUser('cd004_t15_cnt');
        $this->seedNotification($uid, 'cd004.ع.راجع.1');   // غير مقروء
        $this->seedNotification($uid, 'cd004.ع.تم.1', 1);  // مقروء
        $this->seedNotification($uid, 'cd004.ع.راجع.2');   // غير مقروء
        $this->assertSame(2, (new Notification())->unreadCount($uid));
    }

    public function testForUserOrdersNewestFirstAndKeepsLegacyRows(): void
    {
        $this->skipIfDbUnavailable('بند 15');
        $uid = $this->seedUser('cd004_t15_order');
        // إشعارات "قديمة" (قبل بند 14) — related_url فارغة وأنواع enum كاملة
        $this->seedNotification($uid, 'cd004.قديم.1', 0, 'danger', null, '2026-09-15 10:00:00');
        $this->seedNotification($uid, 'cd004.قديم.2', 1, 'warning', null, '2026-09-15 11:00:00');
        // إشعارات جديدة (بعد بند 14)
        $this->seedNotification($uid, 'cd004.جديد.1', 0, 'info', '/admin/users', '2026-09-21 10:00:00');
        $rows = (new Notification())->forUser($uid);
        $this->assertCount(3, $rows);
        $titles = array_column($rows, 'title');
        $this->assertSame('cd004.جديد.1', $titles[0]);   // الأحدث أولًا
        $this->assertSame('cd004.قديم.1', $titles[2]);   // الأقدم أخيرًا
        $legacy = null;
        foreach ($rows as $r) {
            if ($r['title'] === 'cd004.قديم.1') {
                $legacy = $r;
            }
        }
        $this->assertNotNull($legacy);
        $this->assertNull($legacy['related_url']);        // legacy بلا رابط — تُعرض
        $this->assertSame('danger', $legacy['type']);
    }

    public function testRecentForUserLimitsToFiveNewestFirst(): void
    {
        $this->skipIfDbUnavailable('بند 15');
        $uid = $this->seedUser('cd004_t15_recent');
        for ($i = 7; $i >= 1; $i--) {
            $this->seedNotification($uid, sprintf('cd004.ر.%d', $i), 0, 'info', null, sprintf('2026-09-%02d 10:00:00', $i));
        }
        $recent = (new Notification())->recentForUser($uid, 5);
        $this->assertCount(5, $recent);
        $this->assertSame('cd004.ر.7', $recent[0]['title']);
        $this->assertSame('cd004.ر.3', $recent[4]['title']);
    }

    public function testMarkReadIsScopedToOwningUser(): void
    {
        $this->skipIfDbUnavailable('بند 15');
        $a = $this->seedUser('cd004_t15_read_a');
        $b = $this->seedUser('cd004_t15_read_b');
        $aId = $this->seedNotification($a, 'cd004.أ.غير.مقروء');
        $bId = $this->seedNotification($b, 'cd004.ب.غير.مقروء');
        $model = new Notification();
        // A يحاول تعليم إشعار B — لا أثر
        $model->markRead($bId, $a);
        $this->assertSame(0, $this->isReadOf($bId));
        // A يعلّم إشعاره
        $model->markRead($aId, $a);
        $this->assertSame(1, $this->isReadOf($aId));
    }

    public function testMarkAllReadIsScopedToOwningUser(): void
    {
        $this->skipIfDbUnavailable('بند 15');
        $a = $this->seedUser('cd004_t15_all_a');
        $b = $this->seedUser('cd004_t15_all_b');
        $this->seedNotification($a, 'cd004.أ.1');
        $this->seedNotification($a, 'cd004.أ.2');
        $this->seedNotification($b, 'cd004.ب.1');
        (new Notification())->markAllRead($a);
        $this->assertSame(0, (new Notification())->unreadCount($a));
        $this->assertSame(1, (new Notification())->unreadCount($b));
    }

    public function testNotifyAdminsStoresNewNotificationRows(): void
    {
        $this->skipIfDbUnavailable('بند 15');
        $admin = $this->seedUser('cd004_t15_staff');
        $this->testDb()->prepare(
            "INSERT INTO user_roles (user_id, role_id)
             VALUES (?, (SELECT id FROM roles WHERE name = 'Admin' AND is_system_role = 1 LIMIT 1))"
        )->execute([$admin]);
        Auth::notifyAdmins('cd004.احفظ.جديد', 'رسالة بند 15');
        $st = $this->testDb()->prepare("SELECT COUNT(*) FROM notifications
                                        WHERE user_id = ? AND title = 'cd004.احفظ.جديد' AND is_read = 0 AND type = 'warning'");
        $st->execute([$admin]);
        $this->assertGreaterThan(0, (int)$st->fetchColumn());
    }

    // ======================================================================
    // اختبارات الواجهة (HTTP عبر CLI)
    // ======================================================================

    public function testScreenRendersAllRowsIncludingLegacy(): void
    {
        $this->skipIfDbUnavailable('بند 15');
        $uid = $this->seedUser('cd004_t15_screen');
        $this->seedNotification($uid, 'cd004.قديم.خطر', 0, 'danger', null, '2026-09-15 10:00:00');
        $this->seedNotification($uid, 'cd004.قديم.تنبيه', 1, 'warning', null, '2026-09-15 09:00:00');
        $this->seedNotification($uid, 'cd004.جديد.رابط', 0, 'info', '/admin/users', '2026-09-21 10:00:00');
        $this->seedNotification($uid, 'cd004.جديد.نجاح', 1, 'success', null, '2026-09-21 09:00:00');

        $html = $this->run($this->indexScript($uid));

        $this->assertStringContainsString('SCREEN_DONE', $html);
        // كل الإشعارات تُعرض — القديمة (قبل بند 14) والجديدة على السواء
        $this->assertStringContainsString('cd004.قديم.خطر', $html);
        $this->assertStringContainsString('cd004.قديم.تنبيه', $html);
        $this->assertStringContainsString('cd004.جديد.رابط', $html);
        $this->assertStringContainsString('cd004.جديد.نجاح', $html);
        // رابط الإشعار الجديد مربوط، والقديم بلا رابط لا يكسر الصفحة
        $this->assertStringContainsString('admin/users', $html);
        // تسميات الأنواع العربية
        $this->assertStringContainsString('خطر', $html);
        $this->assertStringContainsString('تنبيه', $html);
        $this->assertStringContainsString('نجاح', $html);
        $this->assertStringContainsString('معلومات', $html);
        // تمييز غير المقروء (2 صف من 4)
        $unreadCount = substr_count($html, 'notification-item--unread');
        $this->assertTrue($unreadCount >= 2, 'متوقع صفّين غير مقروءين مميزين، وجد: ' . $unreadCount);
        $this->assertFalse(str_contains($html, 'لا توجد إشعارات حالياً'));
    }

    public function testScreenShowsEmptyState(): void
    {
        $this->skipIfDbUnavailable('بند 15');
        $uid = $this->seedUser('cd004_t15_empty_screen');
        $html = $this->run($this->indexScript($uid));
        $this->assertStringContainsString('لا توجد إشعارات حالياً', $html);
        $this->assertStringContainsString('SCREEN_DONE', $html);
    }

    public function testScreenRequiresAuthentication(): void
    {
        $this->skipIfDbUnavailable('بند 15');
        $out = $this->run($this->guestScript());
        // البوابة تُخرج فورًا بلا عرض الشاشة (لا SCREEN_DONE ولا بقاء بعد البوابة) —
        // قيد CLI: الوجهة Location لا تُقرأ (LOCATION=sent دائمًا)، الوجهة المنفردة في الكود هي /login.
        $this->assertFalse(str_contains($out, 'SCREEN_DONE'));
        $this->assertFalse(str_contains($out, 'SURVIVED_GUEST'));
        $this->assertFalse(str_contains($out, 'ERROR='));
    }

    public function testMarkReadViaPostPersistsAndRedirects(): void
    {
        $this->skipIfDbUnavailable('بند 15');
        $uid = $this->seedUser('cd004_t15_post_read');
        $nid = $this->seedNotification($uid, 'cd004.بوست.مفرد');
        $out = $this->run($this->postActionScript('one', $uid, $nid));
        $this->assertSame(1, $this->isReadOf($nid));
        // قيد CLI: وجهة Location لا تُقرأ — التأكيد السلوكي: خروج بلا بقاء بعد الإجراء
        $this->assertFalse(str_contains($out, 'SURVIVED_ACTION'));
        $this->assertFalse(str_contains($out, 'ERROR='));
    }

    public function testMarkAllViaPostPersistsAndRedirects(): void
    {
        $this->skipIfDbUnavailable('بند 15');
        $uid = $this->seedUser('cd004_t15_post_all');
        $n1 = $this->seedNotification($uid, 'cd004.بوست.الكل.1');
        $n2 = $this->seedNotification($uid, 'cd004.بوست.الكل.2');
        $other = $this->seedUser('cd004_t15_post_other');
        $no = $this->seedNotification($other, 'cd004.بوست.الغير');
        $out = $this->run($this->postActionScript('all', $uid));
        $this->assertSame(1, $this->isReadOf($n1));
        $this->assertSame(1, $this->isReadOf($n2));
        $this->assertSame(0, $this->isReadOf($no));   // إشعار المستخدم الآخر يبقى
        // قيد CLI: وجهة Location لا تُقرأ — التأكيد السلوكي: خروج بلا بقاء بعد الإجراء
        $this->assertFalse(str_contains($out, 'SURVIVED_ACTION'));
        $this->assertFalse(str_contains($out, 'ERROR='));
    }
}