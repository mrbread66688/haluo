<?php
require_once 'config.php';
require_once 'functions.php';

if (!isAdmin()) {
    die('无权访问');
}

// 配置备份路径
$backupDir = 'backups/';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// 生成备份文件名
$backupFile = $backupDir . 'csm_backup_' . date('Y-m-d_H-i-s') . '.sql';

// 执行备份命令
$command = "mysqldump --user=" . DB_USER . " --password=" . DB_PASS . " --host=" . DB_HOST . " " . DB_NAME . " > " . $backupFile;
system($command, $output);

if ($output === 0) {
    echo "备份成功: " . $backupFile;
} else {
    echo "备份失败";
}
?>