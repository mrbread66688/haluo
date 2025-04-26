<?php
require_once 'config.php';

// 检查用户是否登录且有管理员权限
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// 初始化变量
$errors = [];
$success = false;
$userData = [
    'username' => '',
    'real_name' => '',
    'phone' => '',
    'role' => 'staff', // 默认为staff而非user
    'status' => 1      // 默认为1(激活)而非active
];

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取并清理输入数据
    $userData = [
        'username' => trim($_POST['username'] ?? ''),
        'real_name' => trim($_POST['real_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
        'role' => $_POST['role'] ?? 'staff',
        'status' => isset($_POST['status']) ? 1 : 0  // 复选框形式
    ];

    // 验证输入
    if (empty($userData['username'])) {
        $errors['username'] = '用户名不能为空';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $userData['username'])) {
        $errors['username'] = '用户名必须是4-20位的字母、数字或下划线';
    } else {
        // 检查用户名是否已存在
        $stmt = $pdo->prepare("SELECT id FROM Users WHERE username = ?");
        $stmt->execute([$userData['username']]);
        if ($stmt->fetch()) {
            $errors['username'] = '该用户名已被使用';
        }
    }

    if (empty($userData['real_name'])) {
        $errors['real_name'] = '真实姓名不能为空';
    }

    if (!empty($userData['phone']) && !preg_match('/^1[3-9]\d{9}$/', $userData['phone'])) {
        $errors['phone'] = '请输入有效的手机号码';
    }

    if (empty($userData['password'])) {
        $errors['password'] = '密码不能为空';
    } elseif (strlen($userData['password']) < 6) {
        $errors['password'] = '密码长度不能少于6位';
    } elseif ($userData['password'] !== $userData['password_confirm']) {
        $errors['password_confirm'] = '两次输入的密码不一致';
    }

    // 如果没有错误，创建用户
    if (empty($errors)) {
        try {
            $user = new User($pdo);
            $createData = [
                'username' => $userData['username'],
                'password' => $userData['password'],
                'real_name' => $userData['real_name'],
                'phone' => $userData['phone'],
                'role' => $userData['role'],
                'status' => $userData['status']
            ];
            
            if ($user->createUser($createData)) {
                $success = true;
                // 清空表单数据
                $userData = [
                    'username' => '',
                    'real_name' => '',
                    'phone' => '',
                    'role' => 'staff',
                    'status' => 1
                ];
            } else {
                $errors['general'] = '创建用户失败，请重试';
            }
        } catch (PDOException $e) {
            $errors['general'] = '数据库错误: ' . $e->getMessage();
        }
    }
}

// 包含头部
include 'views/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">添加新用户</h3>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            用户添加成功！<a href="users.php" class="alert-link">返回用户列表</a>
                        </div>
                    <?php elseif (isset($errors['general'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($errors['general']) ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="form-group">
                            <label for="username">用户名</label>
                            <input type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                                   id="username" name="username" value="<?= htmlspecialchars($userData['username']) ?>" required>
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['username']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="real_name">真实姓名</label>
                            <input type="text" class="form-control <?= isset($errors['real_name']) ? 'is-invalid' : '' ?>" 
                                   id="real_name" name="real_name" value="<?= htmlspecialchars($userData['real_name']) ?>" required>
                            <?php if (isset($errors['real_name'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['real_name']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="phone">手机号码</label>
                            <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                                   id="phone" name="phone" value="<?= htmlspecialchars($userData['phone']) ?>">
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['phone']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="password">密码</label>
                                <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                       id="password" name="password" required>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback">
                                        <?= htmlspecialchars($errors['password']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="password_confirm">确认密码</label>
                                <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" 
                                       id="password_confirm" name="password_confirm" required>
                                <?php if (isset($errors['password_confirm'])): ?>
                                    <div class="invalid-feedback">
                                        <?= htmlspecialchars($errors['password_confirm']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="role">角色</label>
                                <select class="form-control" id="role" name="role">
                                    <option value="staff" <?= $userData['role'] === 'staff' ? 'selected' : '' ?>>普通员工</option>
                                    <option value="admin" <?= $userData['role'] === 'admin' ? 'selected' : '' ?>>管理员</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <div class="form-check mt-4 pt-2">
                                    <input type="checkbox" class="form-check-input" id="status" name="status" value="1" <?= $userData['status'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="status">激活账户</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">添加用户</button>
                            <a href="users.php" class="btn btn-secondary">取消</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 包含尾部
include 'views/footer.php';
?>