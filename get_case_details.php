<?php
require_once __DIR__ . '/config.php';

if (!isLoggedIn() || empty($_GET['id'])) {
    die('无权访问');
}

$caseModel = new CaseModel($pdo);
$case = $caseModel->getCaseById((int)$_GET['id']);

if (!$case) {
    die('案件不存在');
}
?>

<div class="row">
    <div class="col-md-6">
        <h5>基本信息</h5>
        <table class="table table-bordered">
            <tr>
                <th width="30%">报案号</th>
                <td><?= safeOutput($case['case_number']) ?></td>
            </tr>
            <tr>
                <th>保单号</th>
                <td><?= safeOutput($case['policy_number']) ?></td>
            </tr>
            <tr>
                <th>被保险人</th>
                <td><?= safeOutput($case['insured_name']) ?></td>
            </tr>
            <tr>
                <th>联系电话</th>
                <td><?= safeOutput($case['insured_phone']) ?></td>
            </tr>
            <tr>
                <th>身份证号</th>
                <td><?= safeOutput($case['id_card']) ?></td>
            </tr>
            <tr>
                <th>年龄/性别</th>
                <td><?= safeOutput($case['age']) ?>岁 / <?= safeOutput($case['gender']) ?></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h5>案件信息</h5>
        <table class="table table-bordered">
            <tr>
                <th width="30%">产品类型</th>
                <td><?= safeOutput($case['product_type']) ?></td>
            </tr>
            <tr>
                <th>报案时间</th>
                <td><?= formatDateTime($case['report_time']) ?></td>
            </tr>
            <tr>
                <th>出险时间</th>
                <td><?= formatDateTime($case['accident_time']) ?></td>
            </tr>
            <tr>
                <th>事故责任</th>
                <td><?= safeOutput($case['accident_liability']) ?></td>
            </tr>
            <tr>
                <th>案件状态</th>
                <td><span class="badge bg-<?= getStatusBadgeClass($case['case_status']) ?>"><?= safeOutput($case['case_status']) ?></span></td>
            </tr>
        </table>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <h5>出险信息</h5>
        <table class="table table-bordered">
            <tr>
                <th width="30%">出险地点</th>
                <td><?= safeOutput($case['accident_province'].$case['accident_city'].$case['accident_district']) ?></td>
            </tr>
            <tr>
                <th>详细地址</th>
                <td><?= safeOutput($case['address']) ?></td>
            </tr>
            <tr>
                <th>出险原因</th>
                <td><?= safeOutput($case['accident_reason']) ?></td>
            </tr>
            <tr>
                <th>事故类型</th>
                <td><?= safeOutput($case['accident_type']) ?></td>
            </tr>
            <tr>
                <th>出险经过</th>
                <td><?= nl2br(safeOutput($case['accident_description'])) ?></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h5>理赔信息</h5>
        <table class="table table-bordered">
            <tr>
                <th width="30%">就医情况</th>
                <td><?= safeOutput($case['medical_status']) ?></td>
            </tr>
            <tr>
                <th>受伤部位</th>
                <td><?= safeOutput($case['injury_part']) ?></td>
            </tr>
            <tr>
                <th>三者就医</th>
                <td><?= safeOutput($case['third_party_medical_status']) ?></td>
            </tr>
            <tr>
                <th>估损金额</th>
                <td><?= formatCurrency($case['estimated_amount']) ?></td>
            </tr>
            <tr>
                <th>赔付金额</th>
                <td><?= formatCurrency($case['paid_amount']) ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <h5>跟踪信息</h5>
        <table class="table table-bordered">
            <tr>
                <th width="15%">接报案人</th>
                <td width="35%"><?= safeOutput($case['reporter_id']) ?></td>
                <th width="15%">结案日期</th>
                <td width="35%"><?= formatDateTime($case['close_date'], 'Y-m-d') ?></td>
            </tr>
            <tr>
                <th>最近跟踪</th>
                <td><?= formatDateTime($case['last_track_date'], 'Y-m-d') ?></td>
                <th>距报案天数</th>
                <td><?= safeOutput($case['days_since_report']) ?>天</td>
            </tr>
            <tr>
                <th>距回访天数</th>
                <td><?= safeOutput($case['days_since_last_track']) ?>天</td>
                <th>备注</th>
                <td><?= safeOutput($case['remark']) ?></td>
            </tr>
            <tr>
                <th>跟踪记录</th>
                <td colspan="3"><?= nl2br(safeOutput($case['last_track_record'])) ?></td>
            </tr>
            <tr>
                <th>案件进展</th>
                <td colspan="3"><?= nl2br(safeOutput($case['case_progress'])) ?></td>
            </tr>
        </table>
    </div>
</div>