<?php
require_once 'config.php';
require_once 'functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        $user = new User($pdo);
        if ($user->login($username, $password)) {
            redirect('index.php');
        } else {
            $error = '用户名或密码错误';
        }
    }
}

$title = '登录';
require 'views/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <img src="assets/images/logo.png" alt="Logo" width="200" class="mb-3">
                    <h2 class="h4"><?php echo safeOutput(SITE_NAME); ?></h2>
                    <p class="text-muted">请输入您的账号密码登录系统</p>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?= safeOutput($_POST['username'] ?? '') ?>" required autofocus>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> 登录
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center py-3">
                <small class="text-muted">© <?= date('Y') ?> <?= safeOutput(SITE_NAME) ?></small>
            </div>
        </div>
    </div>
</div>

<?php require 'views/footer.php'; ?>