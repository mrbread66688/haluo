<?php
require_once __DIR__ . '/config.php';

// 权限验证
if (!isLoggedIn() || $_SESSION['role'] != 'admin') {
    redirect('login.php');
}

$caseModel = new CaseModel($pdo);

// 处理文件上传
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['import_file'])) {
    try {
        // 验证文件
        $file = $_FILES['import_file'];
        if ($file['error'] != UPLOAD_ERR_OK) {
            throw new Exception("文件上传失败: 错误代码 " . $file['error']);
        }
        
        // 限制文件类型
        $allowedTypes = [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'text/plain'
        ];
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("只支持CSV或Excel文件");
        }

        // 解析文件
        $data = parseImportFile($file['tmp_name']);
        
        // 调试模式：显示解析后的数据
        if (isset($_GET['debug'])) {
            echo '<div class="container mt-4">';
            echo '<h3>调试模式 - 解析后的数据</h3>';
            echo '<pre>'.print_r($data, true).'</pre>';
            echo '</div>';
            exit();
        }
        
        // 批量导入
        $successCount = 0;
        $totalRows = count($data);
        $errors = [];
        
        foreach ($data as $index => $row) {
            try {
                // 清洗数据：转换空字符串为NULL，并为必填字段设置默认值
                $sanitizedRow = [];
                foreach ($row as $key => $value) {
                    $sanitizedRow[$key] = ($value === '' || $value === null) ? null : $value;
                }
                
                // 为不能为NULL的字段设置默认值
                $sanitizedRow['accident_district'] = $sanitizedRow['accident_district'] ?? '未知地区';
                // 可以根据需要添加其他必填字段的默认值
                // $sanitizedRow['other_required_field'] = $sanitizedRow['other_required_field'] ?? '默认值';
                
                if ($caseModel->importCase($sanitizedRow)) {
                    $successCount++;
                }
            } catch (Exception $e) {
                $errors[] = "第 ".($index+1)." 行: ".$e->getMessage();
                error_log("导入第 ".($index+1)." 行失败: ".$e->getMessage());
            }
        }
        
        // 准备结果消息
        $resultMessage = "成功导入 {$successCount}/{$totalRows} 条数据";
        if (!empty($errors)) {
            $resultMessage .= "<br><br>以下行导入失败:<br>".implode("<br>", array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $resultMessage .= "<br>...及其他 ". (count($errors) - 5) ." 个错误";
            }
        }
        
        $_SESSION['import_result'] = $resultMessage;
        redirect('cases.php');
        
    } catch (Exception $e) {
        $error = "导入失败: " . $e->getMessage();
        error_log("导入失败: ".$e->getMessage());
    }
}

// 显示导入页面
include 'views/header.php';
?>
<div class="container mt-4">
    <h2>案件数据导入</h2>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= safeOutput($error) ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['import_result'])): ?>
        <div class="alert alert-<?= strpos($_SESSION['import_result'], '失败') !== false ? 'danger' : 'success' ?>">
            <?= nl2br(safeOutput($_SESSION['import_result'])) ?>
        </div>
        <?php unset($_SESSION['import_result']); ?>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">选择文件（CSV/Excel）</label>
                    <input type="file" class="form-control" name="import_file" accept=".csv,.xls,.xlsx" required>
                    <div class="form-text">
                        请使用<a href="export.php" download>导出模板</a>格式
                        <br>支持最大文件大小: <?= ini_get('upload_max_filesize') ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">导入数据</button>
                <a href="cases.php" class="btn btn-secondary">取消</a>
                
                <?php if (isAdmin()): ?>
                    <a href="?debug=1" class="btn btn-info float-end">调试模式</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <div class="mt-4">
        <h4>导入说明：</h4>
        <ul>
            <li>请确保文件编码为UTF-8</li>
            <li>空值将自动设置为默认值</li>
            <li>日期格式：YYYY-MM-DD 或 YYYY-MM-DD HH:MM</li>
            <li>首次导入建议使用调试模式检查数据</li>
            <li>系统会自动处理缺失的必要字段</li>
        </ul>
    </div>
</div>
<?php include 'views/footer.php'; ?>