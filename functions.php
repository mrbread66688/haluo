<?php
/**
 * 案件管理系统 - 核心函数库
 * 版本：1.3.7
 * 最后更新：2023-12-15
 * 修改：添加安全分页函数
 */

// ==================== 通用功能函数 ====================

/**
 * 检查用户是否已登录
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * 检查当前用户是否是管理员
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * 检查当前用户是否是普通员工
 */
function isStaff() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'staff';
}

/**
 * 检查当前用户是否有权限访问案件
 */
function canAccessCase($caseReporterId) {
    if (isAdmin()) return true;
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] == $caseReporterId;
}

/**
 * 安全过滤HTML输出
 */
function safeOutput($string, $nl2br = false) {
    $string = htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    return $nl2br ? nl2br($string) : $string;
}

/**
 * 页面重定向
 */
function redirect($url, $statusCode = 303, $terminate = true) {
    if (headers_sent()) {
        echo "<script>window.location.href='{$url}';</script>";
    } else {
        header("Location: {$url}", true, $statusCode);
    }
    if ($terminate) exit();
}

// ==================== 验证函数 ====================

/**
 * 验证手机号格式
 */
function validatePhone($phone) {
    return preg_match('/^1[3-9]\d{9}$/', trim($phone ?? ''));
}

/**
 * 验证身份证号格式
 */
function validateIDCard($idCard) {
    return preg_match('/^\d{17}[\dXx]$/', trim($idCard ?? ''));
}

/**
 * 验证电子邮件格式
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// ==================== 格式化函数 ====================

/**
 * 安全生成分页HTML (避免sprintf参数问题)
 */
function safeGeneratePagination(int $totalItems, int $currentPage, array $queryParams, int $pagesToShow = 5): string {
    $totalPages = max(1, ceil($totalItems / PAGE_SIZE));
    if ($totalPages <= 1) return '';
    
    // 移除旧的page参数
    unset($queryParams['page']);
    
    $startPage = max(1, $currentPage - floor($pagesToShow / 2));
    $endPage = min($totalPages, $startPage + $pagesToShow - 1);
    $startPage = max(1, $endPage - $pagesToShow + 1);
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // 上一页按钮
    $prevClass = $currentPage <= 1 ? ' disabled' : '';
    $prevParams = $queryParams;
    $prevParams['page'] = $currentPage - 1;
    $html .= '<li class="page-item'.$prevClass.'">';
    $html .= '<a class="page-link" href="cases.php?'.htmlspecialchars(http_build_query($prevParams)).'" aria-label="Previous">';
    $html .= '<span aria-hidden="true">&laquo;</span></a></li>';
    
    // 页码按钮
    for ($i = $startPage; $i <= $endPage; $i++) {
        $activeClass = $i == $currentPage ? ' active' : '';
        $pageParams = $queryParams;
        $pageParams['page'] = $i;
        $html .= '<li class="page-item'.$activeClass.'">';
        $html .= '<a class="page-link" href="cases.php?'.htmlspecialchars(http_build_query($pageParams)).'">'.$i.'</a></li>';
    }
    
    // 下一页按钮
    $nextClass = $currentPage >= $totalPages ? ' disabled' : '';
    $nextParams = $queryParams;
    $nextParams['page'] = $currentPage + 1;
    $html .= '<li class="page-item'.$nextClass.'">';
    $html .= '<a class="page-link" href="cases.php?'.htmlspecialchars(http_build_query($nextParams)).'" aria-label="Next">';
    $html .= '<span aria-hidden="true">&raquo;</span></a></li>';
    
    $html .= '</ul></nav>';
    
    // 添加总记录数信息
    $html .= '<div class="text-center text-muted mt-2 small">';
    $html .= '共 '.number_format($totalItems).' 条记录，每页显示 '.PAGE_SIZE.' 条';
    $html .= '</div>';
    
    return $html;
}

/**
 * 生成分页HTML (Bootstrap 5样式) - 旧版(保留兼容)
 */
function generatePagination($totalItems, $currentPage, $urlPattern, $pagesToShow = 5) {
    // 转换旧版调用为安全版本
    $queryString = parse_url($urlPattern, PHP_URL_QUERY);
    parse_str($queryString, $queryParams);
    
    // 移除page参数
    unset($queryParams['page']);
    
    return safeGeneratePagination($totalItems, $currentPage, $queryParams, $pagesToShow);
}

/**
 * 根据案件状态获取对应的Bootstrap badge类
 */
function getStatusBadgeClass($status) {
    switch ($status) {
        case '已赔付': return 'success';
        case '处理中': return 'primary';
        case '撤案': return 'secondary';
        case '待确定': return 'warning';
        default: return 'info';
    }
}

/**
 * 生成select选项的selected属性
 */
function selected($current, $option) {
    return strval($current) === strval($option) ? 'selected' : '';
}

/**
 * 格式化金额显示
 */
function formatCurrency($amount, $decimals = 2) {
    return number_format(floatval($amount ?? 0), $decimals);
}

/**
 * 格式化日期时间显示
 */
function formatDateTime($datetime, $format = 'Y-m-d H:i', $default = '-') {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return $default;
    }
    
    try {
        $date = new DateTime($datetime);
        return $date->format($format);
    } catch (Exception $e) {
        return $default;
    }
}

// ==================== 工具函数 ====================

/**
 * 安全获取数组值
 */
function safeGet($array, $key, $default = null) {
    return $array[$key] ?? $default;
}

/**
 * 记录系统日志
 */
function logMessage($message, $level = 'info', $file = 'system.log') {
    $logDir = dirname(__DIR__) . '/logs/';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $time = date('Y-m-d H:i:s');
    $level = strtoupper($level);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user = $_SESSION['username'] ?? 'guest';
    
    $logContent = "[{$time}] [{$level}] [{$ip}] [{$user}] {$message}" . PHP_EOL;
    
    file_put_contents($logDir . $file, $logContent, FILE_APPEND);
}

/**
 * 生成CSV文件并下载
 */
function downloadCSV($data, $filename = 'export.csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // 输出标题行
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
    }
    
    // 输出数据行
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

/**
 * 获取客户端IP地址
 */
function getClientIP() {
    foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            return $_SERVER[$key];
        }
    }
    return '0.0.0.0';
}

/**
 * 生成随机字符串（最终修复版）
 */
function generateRandomString($length = 8, $type = 'mixed') {
    $num = '0123456789';
    $char = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    
    switch ($type) {
        case 'num': 
            $pool = $num; 
            break;
        case 'char': 
            $pool = $char; 
            break;
        default: 
            $pool = $num . $char;
    }
    
    return substr(
        str_shuffle(
            str_repeat(
                $pool, 
                ceil($length / strlen($pool))
            )
        ),
        0,
        $length
    );
}

/**
 * 检查字符串是否是有效的JSON
 */
function isJson($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * 获取当前URL
 */
function getCurrentUrl($withQueryString = true) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    
    if (!$withQueryString) {
        $uri = strtok($uri, '?');
    }
    
    return $protocol . $host . $uri;
}

/**
 * 生成查询字符串
 */
function buildQueryString($params, $includeEmpty = false) {
    $query = [];
    foreach ($params as $key => $value) {
        if ($includeEmpty || !empty($value)) {
            $query[] = urlencode($key) . '=' . urlencode($value);
        }
    }
    return implode('&', $query);
}

// ==================== 导入导出功能 ====================

/**
 * 解析导入文件（CSV/Excel）
 */
function parseImportFile(string $filePath): array {
    if (!file_exists($filePath)) {
        throw new Exception("上传文件不存在");
    }

    $extension = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));
    
    // 列名映射表（中英文对照）
    $headerMap = [
        '报案号' => 'case_number',
        '保单号/哈啰订单号' => 'policy_number',
        '被保险人' => 'insured_name',
        '被保险人电话' => 'insured_phone',
        '身份证号' => 'id_card',
        '年龄' => 'age',
        '性别' => 'gender',
        '事故责任' => 'accident_liability',
        '产品类型' => 'product_type',
        '报案时间' => 'report_time',
        '出险时间' => 'accident_time',
        '出险省份' => 'accident_province',
        '出险城市' => 'accident_city',
        '出险区域' => 'accident_district',
        '详细地址' => 'address',
        '出险原因' => 'accident_reason',
        '事故类型' => 'accident_type',
        '出险经过' => 'accident_description',
        '就医情况' => 'medical_status',
        '标的受伤部位' => 'injury_part',
        '三者就医情况' => 'third_party_medical_status',
        '三者受伤部位' => 'third_party_injury_part',
        '估损金额' => 'estimated_amount',
        '赔付金额' => 'paid_amount',
        '案件状态' => 'case_status',
        '结案日期' => 'close_date',
        '最近跟踪时间' => 'last_track_date',
        '最新跟踪记录' => 'last_track_record',
        '案件进展' => 'case_progress',
        '备注' => 'remark',
        '距离最新回访天数' => 'days_since_last_track',
        '距离报案时间天数' => 'days_since_report',
        '接报案人' => 'reporter_name'
    ];

    // CSV处理
    if ($extension === 'csv') {
        if (($handle = fopen($filePath, "r")) === false) {
            throw new Exception("无法打开CSV文件");
        }
        
        $data = [];
        $headers = fgetcsv($handle);
        
        // 清洗并转换列名
        $headers = array_map(function($header) use ($headerMap) {
            $cleanHeader = trim($header);
            return $headerMap[$cleanHeader] ?? $cleanHeader;
        }, $headers);

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $cleanRow = array_map(function($value) {
                    return trim($value);
                }, $row);
                $data[] = array_combine($headers, $cleanRow);
            }
        }
        fclose($handle);
        return $data;
    } 
    // Excel处理（需要PhpSpreadsheet）
    elseif (in_array($extension, ['xls', 'xlsx'])) {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            throw new Exception("请安装PhpSpreadsheet: composer require phpoffice/phpspreadsheet");
        }
        
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        
        if (count($sheetData) < 2) {
            throw new Exception("Excel文件没有有效数据");
        }
        
        $headers = $sheetData[0];
        // 清洗并转换列名
        $headers = array_map(function($header) use ($headerMap) {
            $cleanHeader = trim($header);
            return $headerMap[$cleanHeader] ?? $cleanHeader;
        }, $headers);

        $data = [];
        
        for ($i = 1; $i < count($sheetData); $i++) {
            $row = $sheetData[$i];
            if (count($row) !== count($headers)) continue;
            
            $cleanRow = array_map(function($value) {
                if ($value instanceof \DateTime) {
                    return $value->format('Y-m-d H:i');
                }
                return trim(strval($value));
            }, $row);
            
            $data[] = array_combine($headers, $cleanRow);
        }
        
        return $data;
    }
    
    throw new Exception("不支持的文件格式: .$extension");
}

/**
 * 生成导入模板
 */
function generateImportTemplate() {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="案件导入模板.xls"');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
    <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
    <body>
    <table border="1">
    <tr>
        <th>报案号</th>
        <th>保单号</th>
        <th>被保险人</th>
        <th>联系电话</th>
        <th>身份证号</th>
        <th>事故责任</th>
        <th>产品类型</th>
        <th>报案时间</th>
        <th>出险时间</th>
    </tr>
    <tr>
        <td>CN20230001</td>
        <td>PL20230001</td>
        <td>张三</td>
        <td>13800138000</td>
        <td>310113199001011234</td>
        <td>全责</td>
        <td>助动车意外险</td>
        <td>2023-01-01 10:00</td>
        <td>2023-01-01 09:30</td>
    </tr>
    </table>
    </body>
    </html>';
    exit();
}