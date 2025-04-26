<?php
require_once 'config.php';

header('Content-Type: text/plain');

echo "=== 地区数据完整性检查 ===\n\n";

// 检查北京市数据
$provinceCode = '110000';
$stmt = $pdo->prepare("SELECT * FROM sys_region WHERE code = ? AND level = 1");
$stmt->execute([$provinceCode]);
$beijing = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$beijing) {
    die("错误: 数据库中不存在北京市数据(code=110000)\n");
}

echo "北京市信息:\n";
print_r($beijing);

// 检查北京市下级区域
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sys_region WHERE parent_code = ? AND level = 2");
$stmt->execute([$provinceCode]);
$districtCount = $stmt->fetchColumn();

echo "\n北京市下级区域数量: $districtCount\n";

if ($districtCount > 0) {
    $stmt = $pdo->prepare("SELECT code, name FROM sys_region WHERE parent_code = ? AND level = 2 LIMIT 5");
    $stmt->execute([$provinceCode]);
    $districts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n前5个区域:\n";
    print_r($districts);
} else {
    echo "\n警告: 没有找到北京市的下级区域数据\n";
}
