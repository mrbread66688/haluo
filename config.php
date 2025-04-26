<?php
// ==================== 错误报告设置 ====================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// ==================== 路径常量定义 ====================
define('BASE_PATH', __DIR__);
define('PUBLIC_PATH', BASE_PATH . '/public');
define('ASSETS_PATH', PUBLIC_PATH . '/assets');
define('JS_PATH', ASSETS_PATH . '/js');
define('VENDOR_PATH', BASE_PATH . '/vendor');
define('LOG_PATH', BASE_PATH . '/logs');

// ==================== 数据库配置 ====================
define('DB_HOST', 'localhost');
define('DB_USER', 'ibao');
define('DB_PASS', 'ibao123');
define('DB_NAME', 'haluo329');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// ==================== 系统配置 ====================
define('SITE_NAME', '案件管理系统');
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/csm-system');
define('DEFAULT_TIMEZONE', 'Asia/Shanghai');
define('PAGE_SIZE', 20);
define('PASSWORD_SALT', 'your_random_salt_here');
define('CHARTJS_VERSION', '3.9.1');

// ==================== 初始化检查 ====================
if (!file_exists(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
    file_put_contents(LOG_PATH . '/.htaccess', 'Deny from all');
}

// ==================== 时区设置 ====================
date_default_timezone_set(DEFAULT_TIMEZONE);

// ==================== 依赖加载 ====================
$composerAutoload = VENDOR_PATH . '/autoload.php';
if (file_exists($composerAutoload)) {
    require $composerAutoload;
} else {
    die('<div class="alert alert-danger">
        <h3>系统依赖未安装</h3>
        <p>请执行以下命令安装依赖：</p>
        <pre>cd ' . BASE_PATH . '\ncomposer install</pre>
    </div>');
}

// ==================== 会话启动 ====================
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'name' => 'HALLO_SESSID',
        'cookie_lifetime' => 86400,
        'read_and_close'  => false,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax'
    ]);
}

// ==================== 文件加载 ====================
require_once BASE_PATH . '/functions.php';

// ==================== 类自动加载 ====================
spl_autoload_register(function ($class) {
    $file = BASE_PATH . '/classes/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    } else {
        error_log("自动加载失败: 类文件 {$file} 不存在");
        throw new Exception("无法加载类: {$class}");
    }
});

// ==================== 数据库连接 ====================
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '" . DB_CHARSET . "' COLLATE '" . DB_COLLATE . "'"
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // 测试数据库连接
    $testQuery = $pdo->query("SELECT code, name FROM sys_region LIMIT 1");
    if ($testQuery->fetch() === false) {
        throw new PDOException("数据库字段测试失败，请确认code/name字段存在");
    }
    
} catch (PDOException $e) {
    die('<div class="alert alert-danger">
        <h3>数据库连接失败</h3>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <h4>解决方案：</h4>
        <ol>
            <li>执行SQL检查字段名: <code>DESCRIBE sys_region;</code></li>
            <li>确认数据库用户有SELECT权限</li>
            <li>检查表数据: <code>SELECT code, name FROM sys_region LIMIT 1;</code></li>
        </ol>
    </div>');
}

// ==================== 组件检查 ====================
$requiredComponents = [
    'PhpOffice\PhpSpreadsheet\Spreadsheet' => [
        'cmd' => 'composer require phpoffice/phpspreadsheet',
        'test' => 'SELECT 1'
    ],
    'PDO' => [
        'cmd' => '安装PHP PDO扩展',
        'test' => 'SELECT 1'
    ]
];

foreach ($requiredComponents as $class => $info) {
    if (!class_exists($class) && !extension_loaded($class)) {
        die('<div class="alert alert-danger">
            <h3>缺少必要组件: ' . htmlspecialchars($class) . '</h3>
            <p>安装命令: <code>' . htmlspecialchars($info['cmd']) . '</code></p>
        </div>');
    }
}

// ==================== 安全头设置 ====================
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header_remove('X-Powered-By');

// ==================== 区域模型调试开关 ====================
define('DEBUG_REGION_MODEL', true);
if (DEBUG_REGION_MODEL) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
}