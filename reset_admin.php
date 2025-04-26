<?php
require_once 'config.php';
require_once 'classes/User.php';

$user = new User($pdo);
if ($user->resetPassword('admin', 'admin123')) {
    echo "管理员密码已重置为admin123";
} else {
    echo "密码重置失败";
}