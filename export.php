<?php
require_once __DIR__ . '/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$caseModel = new CaseModel($pdo);

// 获取筛选条件
$filters = [];
$validFilters = ['product_type', 'case_status', 'accident_liability'];

foreach ($validFilters as $field) {
    if (!empty($_GET[$field])) {
        $filters[$field] = $_GET[$field];
    }
}

// 处理日期范围筛选
if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $filters['report_time'] = [
        $_GET['start_date'],
        $_GET['end_date']
    ];
}

try {
    // 获取所有符合条件的数据(不分页)
    $cases = $caseModel->getAllCases($filters);
    
    // 设置HTTP头
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment;filename="案件列表_'.date('YmdHis').'.xls"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    
    // 开始输出Excel内容
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<!--[if gte mso 9]>';
    echo '<xml>';
    echo '<x:ExcelWorkbook>';
    echo '<x:ExcelWorksheets>';
    echo '<x:ExcelWorksheet>';
    echo '<x:Name>案件列表</x:Name>';
    echo '<x:WorksheetOptions>';
    echo '<x:DisplayGridlines/>';
    echo '</x:WorksheetOptions>';
    echo '</x:ExcelWorksheet>';
    echo '</x:ExcelWorksheets>';
    echo '</x:ExcelWorkbook>';
    echo '</xml>';
    echo '<![endif]-->';
    echo '</head>';
    echo '<body>';
    
    echo '<table border="1">';
    
    // 输出表头
    echo '<tr>';
    echo '<th>报案号</th>';
    echo '<th>保单号</th>';
    echo '<th>被保险人</th>';
    echo '<th>联系电话</th>';
    echo '<th>身份证号</th>';
    echo '<th>年龄</th>';
    echo '<th>性别</th>';
    echo '<th>事故责任</th>';
    echo '<th>产品类型</th>';
    echo '<th>报案时间</th>';
    echo '<th>出险时间</th>';
    echo '<th>出险省份</th>';
    echo '<th>出险城市</th>';
    echo '<th>出险区域</th>';
    echo '<th>详细地址</th>';
    echo '<th>出险原因</th>';
    echo '<th>事故类型</th>';
    echo '<th>出险经过</th>';
    echo '<th>就医情况</th>';
    echo '<th>受伤部位</th>';
    echo '<th>三者就医</th>';
    echo '<th>估损金额</th>';
    echo '<th>赔付金额</th>';
    echo '<th>案件状态</th>';
    echo '<th>结案日期</th>';
    echo '<th>最近跟踪日期</th>';
    echo '<th>最新跟踪记录</th>';
    echo '<th>案件进展</th>';
    echo '<th>备注</th>';
    echo '<th>距离最新回访天数</th>';
    echo '<th>距离报案时间天数</th>';
    echo '<th>接报案人</th>';
    echo '</tr>';
    
    // 输出数据行
    foreach ($cases as $case) {
        echo '<tr>';
        echo '<td>'.safeOutput($case['case_number']).'</td>';
        echo '<td>'.safeOutput($case['policy_number']).'</td>';
        echo '<td>'.safeOutput($case['insured_name']).'</td>';
        echo '<td>'.safeOutput($case['insured_phone']).'</td>';
        echo '<td>'.safeOutput($case['id_card']).'</td>';
        echo '<td>'.safeOutput($case['age']).'</td>';
        echo '<td>'.safeOutput($case['gender']).'</td>';
        echo '<td>'.safeOutput($case['accident_liability']).'</td>';
        echo '<td>'.safeOutput($case['product_type']).'</td>';
        echo '<td>'.formatDateTime($case['report_time']).'</td>';
        echo '<td>'.formatDateTime($case['accident_time']).'</td>';
        echo '<td>'.safeOutput($case['accident_province']).'</td>';
        echo '<td>'.safeOutput($case['accident_city']).'</td>';
        echo '<td>'.safeOutput($case['accident_district']).'</td>';
        echo '<td>'.safeOutput($case['address']).'</td>';
        echo '<td>'.safeOutput($case['accident_reason']).'</td>';
        echo '<td>'.safeOutput($case['accident_type']).'</td>';
        echo '<td>'.safeOutput($case['accident_description']).'</td>';
        echo '<td>'.safeOutput($case['medical_status']).'</td>';
        echo '<td>'.safeOutput($case['injury_part']).'</td>';
        echo '<td>'.safeOutput($case['third_party_medical_status']).'</td>';
        echo '<td>'.formatCurrency($case['estimated_amount']).'</td>';
        echo '<td>'.formatCurrency($case['paid_amount']).'</td>';
        echo '<td>'.safeOutput($case['case_status']).'</td>';
        echo '<td>'.formatDateTime($case['close_date'], 'Y-m-d').'</td>';
        echo '<td>'.formatDateTime($case['last_track_date'], 'Y-m-d').'</td>';
        echo '<td>'.safeOutput($case['last_track_record']).'</td>';
        echo '<td>'.safeOutput($case['case_progress']).'</td>';
        echo '<td>'.safeOutput($case['remark']).'</td>';
        echo '<td>'.safeOutput($case['days_since_last_track']).'</td>';
        echo '<td>'.safeOutput($case['days_since_report']).'</td>';
        echo '<td>'.safeOutput($case['reporter_id']).'</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body></html>';
    
} catch (Exception $e) {
    // 记录错误日志
    error_log('导出Excel失败: ' . $e->getMessage());
    
    // 显示错误信息
    die('导出Excel时发生错误: ' . $e->getMessage());
}
exit();