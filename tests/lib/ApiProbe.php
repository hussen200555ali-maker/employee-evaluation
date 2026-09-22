<?php
/**
 * مسبار API — يحاكي طلب HTTP كاملاً عبر المسار الحقيقي (مثل public/index.php)
 * دون الحاجة إلى خادم ويب، ويجري في عملية منفصلة لأن استجابات JSON تنهي التنفيذ بـ exit.
 *
 * الاستخدام:
 *   php tests/lib/ApiProbe.php <uri> <method> [apiKey] [bodyFile] [statusFile]
 *
 * المخرجات: يكتب جسم الاستجابة في bodyFile ورمز الحالة في statusFile.
 */

if (php_sapi_name() !== 'cli') {
    exit(1);
}

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';

$uri      = $argv[1] ?? '';
$method   = strtoupper($argv[2] ?? 'GET');
$apiKey   = $argv[3] ?? '';
$bodyFile = $argv[4] ?? '';
$statusFile = $argv[5] ?? '';

if (strpos($uri, '?') !== false) {
    [$path, $qs] = explode('?', $uri, 2);
    $uri = $path;
    parse_str($qs, $qsArr);
    $_GET = array_merge($_GET, $qsArr);
}
$_GET['url'] = $uri;
$_SERVER['REQUEST_METHOD'] = $method;
if ($apiKey !== '') {
    $_SERVER['HTTP_X_API_KEY'] = $apiKey;
}
$_POST = [];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start();
register_shutdown_function(function () use ($bodyFile, $statusFile) {
    $code = http_response_code();
    $body = ob_get_level() ? (string)ob_get_clean() : '';
    if ($bodyFile !== '') {
        file_put_contents($bodyFile, $body);
    }
    if ($statusFile !== '') {
        file_put_contents($statusFile, (string)$code);
    }
});

// نفس ترتيب التنفيذ في public/index.php
\App\Core\Auth::enforceCsrfToken();
$router = require APP_ROOT . '/routes/web.php';
$router->direct(trim($_GET['url'] ?? '', '/'), $method);

exit(0);
