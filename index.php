<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$title = '首页';
require 'views/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="jumbotron">
            <h1 class="display-4">欢迎使用<?php echo SITE_NAME; ?></h1>
            <p class="lead">这是一个专业的保险案件管理系统，帮助您高效管理各类保险案件。</p>
            <hr class="my-4">
            <p>系统提供案件录入、查询、统计分析和数据导出等功能。</p>
            <a class="btn btn-primary btn-lg" href="cases.php" role="button">开始使用</a>
        </div>
    </div>
</div>

<?php require 'views/footer.php'; ?>