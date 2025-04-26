<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'classes/User.php';

// 检查管理员权限
if (!isAdmin()) {
    redirect('login.php');
}

$userModel = new User($pdo);

// 获取要编辑的用户ID
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($userId <= 0) {
    $_SESSION['error_message'] = '无效的用户ID';
    redirect('users.php');
}

// 获取用户信息
$user = $userModel->getUserById($userId);
if (!$user) {
    $_SESSION['error_message'] = '用户不存在';
    redirect('users.php');
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id' => $userId,
        'username' => trim($_POST['username'] ?? ''),
        'real_name' => trim($_POST['real_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'role' => $_POST['role'] ?? 'staff',
        'status' => isset($_POST['status']) ? 1 : 0
    ];

    // 验证数据
    $errors = [];
    
    if (empty($data['username'])) {
        $errors[] = '用户名不能为空';
    }
    
    if (empty($data['real_name'])) {
        $errors[] = '真实姓名不能为空';
    }
    
    if (!empty($data['phone']) && !validatePhone($data['phone'])) {
        $errors[] = '手机号格式不正确';
    }

    // 检查用户名是否已存在（排除当前用户）
    $existingUser = $userModel->getUserByUsername($data['username']);
    if ($existingUser && $existingUser['id'] != $userId) {
        $errors[] = '用户名已存在';
    }

    if (empty($errors)) {
        // 更新用户信息
        if ($userModel->updateUser($data)) {
            $_SESSION['success_message'] = '用户信息更新成功';
            redirect('users.php');
        } else {
            $errors[] = '更新用户信息失败，请稍后再试';
        }
    }
}

$title = '编辑用户';
require 'views/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <h2>编辑用户</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="mb-3">
                <label for="username" class="form-label">用户名</label>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="real_name" class="form-label">真实姓名</label>
                <input type="text" class="form-control" id="real_name" name="real_name" 
                       value="<?php echo htmlspecialchars($user['real_name']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="phone" class="form-label">联系方式</label>
                <input type="text" class="form-control" id="phone" name="phone" 
                       value="<?php echo htmlspecialchars($user['phone']); ?>">
            </div>
            
            <div class="mb-3">
                <label class="form-label">角色</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="role" id="role_staff" 
                               value="staff" <?php echo $user['role'] === 'staff' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="role_staff">普通员工</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="role" id="role_admin" 
                               value="admin" <?php echo $user['role'] === 'admin' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="role_admin">管理员</label>
                    </div>
                </div>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="status" name="status" 
                       <?php echo $user['status'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="status">启用账户</label>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="users.php" class="btn btn-secondary me-md-2">返回</a>
                <button type="submit" class="btn btn-primary">保存更改</button>
            </div>
        </form>
    </div>
</div>

<?php require 'views/footer.php'; ?>