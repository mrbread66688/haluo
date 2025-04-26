<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'classes/User.php';

// 检查管理员权限
if (!isAdmin()) {
    $_SESSION['error_message'] = '您没有权限执行此操作';
    header('Location: login.php');
    exit();
}

// 检查ID参数
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = '未指定要删除的用户';
    header('Location: users.php');
    exit();
}

$userId = (int)$_GET['id'];
$userModel = new User($pdo);

// 获取用户信息用于确认消息
$user = $userModel->getUserById($userId);
if (!$user) {
    $_SESSION['error_message'] = '用户不存在';
    header('Location: users.php');
    exit();
}

// 检查是否是当前登录用户（不能删除自己）
if ($userId === $_SESSION['user_id']) {
    $_SESSION['error_message'] = '不能删除当前登录的账户';
    header('Location: users.php');
    exit();
}

// 检查用户是否有未处理的案件
$caseCount = $userModel->getUserCaseCount($userId);
if ($caseCount > 0) {
    $_SESSION['error_message'] = '该用户仍有负责的案件，请先转移案件再删除';
    header('Location: users.php');
    exit();
}

// 执行删除操作
if ($userModel->deleteUser($userId)) {
    $_SESSION['success_message'] = '用户 ' . htmlspecialchars($user['real_name']) . ' 已成功删除';
} else {
    $_SESSION['error_message'] = '删除用户失败，请稍后再试';
}

header('Location: users.php');
exit();
?>