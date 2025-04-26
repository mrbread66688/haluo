<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$caseModel = new CaseModel($pdo);
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$isAdmin = isAdmin();
$reporterId = $_SESSION['user_id'];

// 处理筛选条件
$filters = [];
$filterFields = ['case_status', 'medical_status'];

foreach ($filterFields as $field) {
    if (!empty($_GET[$field])) {
        $filters[$field] = $_GET[$field];
    }
}

if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $filters['report_time'] = [$_GET['start_date'], $_GET['end_date']];
}

if (!empty($_GET['search_keyword'])) {
    $filters['search_keyword'] = $_GET['search_keyword'];
}

if (!empty($_GET['remark_keyword'])) {
    $filters['remark_keyword'] = $_GET['remark_keyword'];
}

// 处理排序参数
$filters['days_since_last_track_order'] = $_GET['days_since_last_track_order'] ?? '';

$cases = $caseModel->getCases($page, $filters, $isAdmin, $reporterId);
$totalCases = $caseModel->getTotalCases($filters, $isAdmin, $reporterId);
$medicalStatuses = $caseModel->getDistinctValues('medical_status');
$remarkKeywords = $caseModel->getRemarkKeywords();

$title = '案件管理';
require 'views/header.php';
?>

<style>
/* 完整CSS样式 */
.table-container {
    overflow-x: auto;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    margin: 15px 0;
    position: relative;
}

.table-grid {
    display: grid;
    grid-template-columns: 
        120px   /* 操作列 */
        80px    /* 回访天数 */
        80px    /* 报案天数 */
        140px   /* 报案号 */
        150px   /* 保单号/订单号 */
        100px   /* 被保险人 */
        120px   /* 联系电话 */
        180px   /* 身份证号 */
        60px    /* 年龄 */
        60px    /* 性别 */
        100px   /* 事故责任 */
        120px   /* 产品类型 */
        140px   /* 报案时间 */
        140px   /* 出险时间 */
        100px   /* 出险省份 */
        100px   /* 出险城市 */
        100px   /* 出险区域 */
        200px   /* 详细地址 */
        120px   /* 出险原因 */
        120px   /* 事故类型 */
        200px   /* 出险经过 */
        100px   /* 就医情况 */
        150px   /* 标的受伤部位 */
        100px   /* 三者就医 */
        150px   /* 三者受伤部位 */
        120px   /* 估损金额 */
        120px   /* 赔付金额 */
        100px   /* 案件状态 */
        120px   /* 结案日期 */
        140px   /* 最近跟踪 */
        200px   /* 跟踪记录 */
        200px   /* 案件进展 */
        250px   /* 备注 */
        120px;  /* 接报案人 */
    min-width: fit-content;
}

.table-header {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #f8f9fa;
    font-size: 0.85em;
    font-weight: 600;
    padding: 12px 8px;
    border-bottom: 2px solid #dee2e6;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.table-cell {
    padding: 8px;
    font-size: 0.85em;
    border-right: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    min-height: 40px;
    background: white;
    line-height: 1.4;
}

.sticky-col {
    position: sticky;
    left: 0;
    z-index: 2;
    background: white;
    box-shadow: 2px 0 3px -1px rgba(0,0,0,0.05);
}

.filter-panel {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    margin-bottom: 12px;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-label {
    font-size: 0.85em;
    font-weight: 500;
    margin-bottom: 4px;
    color: #495057;
}

.compact-input {
    height: 34px;
    font-size: 0.9em;
    padding: 4px 8px;
    border-radius: 4px;
    border: 1px solid #ced4da;
}

.date-range-group {
    display: grid;
    grid-template-columns: 1fr 24px 1fr;
    align-items: center;
    gap: 4px;
}

.tooltip-cell {
    cursor: help;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    position: relative;
}

.btn-action {
    padding: 2px 6px;
    font-size: 0.8em;
    margin: 0 2px;
    border-radius: 3px;
    transition: all 0.2s ease;
}

.btn-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.pagination {
    margin: 0;
}

.btn-sort {
    min-width: 90px;
    transition: all 0.3s ease;
    position: relative;
}

.btn-sort.active {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
    color: white !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    z-index: 1;
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px 0;
    border-bottom: 1px solid #e9ecef;
}

.empty-state i {
    font-size: 2.5em;
    margin-bottom: 15px;
    color: #6c757d;
}

.currency-cell {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: #2c3e50;
}
</style>

<div class="container-fluid px-3">
    <div class="card border-0 shadow-none">
        <!-- 头部操作栏 -->
        <div class="card-header bg-white border-bottom-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold text-gray-800">
                    <i class="fas fa-clipboard-list me-2 text-primary"></i>案件列表
                </h5>
                <div class="d-flex gap-2">
                    <a href="case_add.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> 新增案件
                    </a>
                    <?php if ($isAdmin): ?>
                    <a href="import.php" class="btn btn-success btn-sm">
                        <i class="fas fa-file-import me-1"></i> 导入
                    </a>
                    <button class="btn btn-info btn-sm" id="exportExcel">
                        <i class="fas fa-file-excel me-1"></i> 导出
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 搜索面板 -->
        <div class="filter-panel">
            <form method="get" class="row g-3">
                <div class="col-12">
                    <div class="filter-row">
                        <!-- 案件状态 -->
                        <div class="filter-group">
                            <label class="filter-label">案件状态</label>
                            <select name="case_status" class="form-select compact-input">
                                <option value="">全部状态</option>
                                <option value="撤案" <?= selected($_GET['case_status'] ?? '', '撤案') ?>>撤案</option>
                                <option value="处理中" <?= selected($_GET['case_status'] ?? '', '处理中') ?>>处理中</option>
                                <option value="已赔付" <?= selected($_GET['case_status'] ?? '', '已赔付') ?>>已赔付</option>
                            </select>
                        </div>

                        <!-- 就医情况 -->
                        <div class="filter-group">
                            <label class="filter-label">就医情况</label>
                            <select name="medical_status" class="form-select compact-input">
                                <option value="">全部情况</option>
                                <?php foreach ($medicalStatuses as $status): ?>
                                <option value="<?= safeOutput($status) ?>" <?= selected($_GET['medical_status'] ?? '', $status) ?>>
                                    <?= safeOutput($status) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- 日期范围 -->
                        <div class="filter-group">
                            <label class="filter-label">报案日期</label>
                            <div class="date-range-group">
                                <input type="date" name="start_date" 
                                    class="form-control compact-input" 
                                    value="<?= safeOutput($_GET['start_date'] ?? '') ?>">
                                <span class="text-muted">至</span>
                                <input type="date" name="end_date" 
                                    class="form-control compact-input" 
                                    value="<?= safeOutput($_GET['end_date'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- 关键字搜索 -->
                        <div class="filter-group">
                            <label class="filter-label">关键字搜索</label>
                            <div class="input-group">
                                <input type="text" name="search_keyword" 
                                    class="form-control compact-input" 
                                    placeholder="报案号/保单号/姓名" 
                                    value="<?= safeOutput($_GET['search_keyword'] ?? '') ?>">
                                <button type="button" class="btn btn-outline-secondary compact-input" 
                                    onclick="this.form.reset()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <!-- 备注搜索 -->
                        <div class="flex-grow-1 me-3">
                            <div class="input-group">
                                <input type="text" name="remark_keyword" 
                                    class="form-control compact-input" 
                                    placeholder="备注关键词"
                                    value="<?= safeOutput($_GET['remark_keyword'] ?? '') ?>"
                                    list="remarkKeywordsList">
                                <datalist id="remarkKeywordsList">
                                    <?php foreach ($remarkKeywords as $keyword): ?>
                                    <option value="<?= safeOutput($keyword) ?>">
                                    <?php endforeach; ?>
                                </datalist>
                                <button type="submit" class="btn btn-primary compact-input">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <!-- 排序按钮 -->
                        <div class="btn-group">
                            <button type="button" data-order="asc" 
                                class="btn btn-sort btn-outline-secondary btn-sm <?= ($_GET['days_since_last_track_order'] ?? '') === 'asc' ? 'active' : '' ?>">
                                回访升序
                            </button>
                            <button type="button" data-order="desc" 
                                class="btn btn-sort btn-outline-secondary btn-sm <?= ($_GET['days_since_last_track_order'] ?? '') === 'desc' ? 'active' : '' ?>">
                                回访降序
                            </button>
                            <input type="hidden" name="days_since_last_track_order" 
                                value="<?= $_GET['days_since_last_track_order'] ?? '' ?>">
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- 表格区域 -->
        <div class="table-container">
            <div class="table-grid">
                <!-- 表头 -->
                <div class="table-header table-row">
                    <div class="table-cell sticky-col">操作</div>
                    <div class="table-cell">回访天数</div>
                    <div class="table-cell">报案天数</div>
                    <div class="table-cell">报案号</div>
                    <div class="table-cell">保单号</div>
                    <div class="table-cell">被保险人</div>
                    <div class="table-cell">联系电话</div>
                    <div class="table-cell">身份证号</div>
                    <div class="table-cell">年龄</div>
                    <div class="table-cell">性别</div>
                    <div class="table-cell">事故责任</div>
                    <div class="table-cell">产品类型</div>
                    <div class="table-cell">报案时间</div>
                    <div class="table-cell">出险时间</div>
                    <div class="table-cell">出险省份</div>
                    <div class="table-cell">出险城市</div>
                    <div class="table-cell">出险区域</div>
                    <div class="table-cell">详细地址</div>
                    <div class="table-cell">出险原因</div>
                    <div class="table-cell">事故类型</div>
                    <div class="table-cell">出险经过</div>
                    <div class="table-cell">就医情况</div>
                    <div class="table-cell">标的受伤</div>
                    <div class="table-cell">三者就医</div>
                    <div class="table-cell">三者受伤</div>
                    <div class="table-cell text-end currency-cell">估损金额</div>
                    <div class="table-cell text-end currency-cell">赔付金额</div>
                    <div class="table-cell">案件状态</div>
                    <div class="table-cell">结案日期</div>
                    <div class="table-cell">最近跟踪</div>
                    <div class="table-cell">跟踪记录</div>
                    <div class="table-cell">案件进展</div>
                    <div class="table-cell">备注</div>
                    <div class="table-cell">接报案人</div>
                </div>

                <!-- 表格内容 -->
                <?php if (!empty($cases)): ?>
                    <?php foreach ($cases as $case): ?>
                    <div class="table-row">
                        <!-- 操作列 -->
                        <div class="table-cell sticky-col">
                            <div class="d-flex gap-1">
                                <a href="case_view.php?id=<?= $case['id'] ?>" 
                                   class="btn-action btn btn-link text-primary"
                                   title="查看" data-bs-toggle="tooltip">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="case_edit.php?id=<?= $case['id'] ?>" 
                                   class="btn-action btn btn-link text-warning"
                                   title="编辑">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="case_tracking.php?id=<?= $case['id'] ?>" 
                                   class="btn-action btn btn-link text-secondary"
                                   title="跟踪">
                                    <i class="fas fa-history"></i>
                                </a>
                            </div>
                        </div>

                        <!-- 数据列 -->
                        <div class="table-cell">
                            <span class="badge bg-<?= getTrackDaysColor($case['days_since_last_track'] ?? 0) ?>">
                                <?= $case['days_since_last_track'] ?? '' ?>
                            </span>
                        </div>
                        <div class="table-cell"><?= $case['days_since_report'] ?? '' ?></div>
                        <div class="table-cell"><?= safeOutput($case['case_number'] ?? '') ?></div>
                        <div class="table-cell"><?= safeOutput($case['policy_number'] ?? '') ?></div>
                        <div class="table-cell"><?= safeOutput($case['insured_name'] ?? '') ?></div>
                        <div class="table-cell"><?= safeOutput($case['insured_phone'] ?? '') ?></div>
                        <div class="table-cell"><?= safeOutput($case['id_card'] ?? '') ?></div>
                        <div class="table-cell"><?= safeOutput($case['age'] ?? '') ?></div>
                        <div class="table-cell"><?= safeOutput($case['gender'] ?? '') ?></div>
                        <div class="table-cell"><?= safeOutput($case['accident_liability'] ?? '') ?></div>
                        <div class="table-cell"><?= safeOutput($case['product_type'] ?? '') ?></div>
                        <div class="table-cell"><?= formatDateTime($case['report_time'] ?? '') ?></div>
                        <div class="table-cell"><?= formatDateTime($case['accident_time'] ?? '') ?></div>
                        <div class="table-cell"><?= safeOutput($case['accident_province'] ?? '') ?></div>
                        <div class="table-cell"><?= safeOutput($case['accident_city'] ?? '') ?></div>
                        <div class="table-cell"><?= safeOutput($case['accident_district'] ?? '') ?></div>
                        <div class="table-cell tooltip-cell" title="<?= safeOutput($case['address'] ?? '') ?>">
                            <?= truncateText($case['address'] ?? '', 15) ?>
                        </div>
                        <div class="table-cell"><?= safeOutput($case['accident_reason'] ?? '') ?></div>
                        <div class="table-cell"><?= safeOutput($case['accident_type'] ?? '') ?></div>
                        <div class="table-cell tooltip-cell" title="<?= safeOutput($case['accident_description'] ?? '') ?>">
                            <?= truncateText($case['accident_description'] ?? '', 20) ?>
                        </div>
                        <div class="table-cell"><?= safeOutput($case['medical_status'] ?? '') ?></div>
                        <div class="table-cell"><?= safeOutput($case['injury_part'] ?? '') ?></div>
                        <div class="table-cell"><?= safeOutput($case['third_party_medical_status'] ?? '') ?></div>
                        <div class="table-cell"><?= safeOutput($case['third_party_injury_part'] ?? '') ?></div>
                        <div class="table-cell text-end currency-cell"><?= formatCurrency($case['estimated_amount'] ?? 0) ?></div>
                        <div class="table-cell text-end currency-cell"><?= formatCurrency($case['paid_amount'] ?? 0) ?></div>
                        <div class="table-cell">
                            <span class="badge bg-<?= getStatusBadgeClass($case['case_status'] ?? '') ?>">
                                <?= safeOutput($case['case_status'] ?? '') ?>
                            </span>
                        </div>
                        <div class="table-cell"><?= formatDateTime($case['close_date'] ?? '', 'Y-m-d') ?></div>
                        <div class="table-cell"><?= formatDateTime($case['last_track_date'] ?? '', 'Y-m-d') ?></div>
                        <div class="table-cell tooltip-cell" title="<?= safeOutput($case['last_track_record'] ?? '') ?>">
                            <?= truncateText($case['last_track_record'] ?? '', 25) ?>
                        </div>
                        <div class="table-cell tooltip-cell" title="<?= safeOutput($case['case_progress'] ?? '') ?>">
                            <?= truncateText($case['case_progress'] ?? '', 25) ?>
                        </div>
                        <div class="table-cell tooltip-cell" title="<?= safeOutput($case['remark'] ?? '') ?>">
                            <?= truncateText($case['remark'] ?? '', 30) ?>
                        </div>
                        <div class="table-cell"><?= safeOutput($case['reporter_name'] ?? '系统') ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="table-row">
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <div class="text-muted">没有找到案件数据</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 分页 -->
        <?php if ($totalCases > PAGE_SIZE): ?>
        <div class="card-footer bg-white py-3">
            <?= safeGeneratePagination(
                $totalCases, 
                $page, 
                $_GET, 
                5
            ) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // 初始化工具提示
    $('[data-bs-toggle="tooltip"]').tooltip({
        trigger: 'hover',
        placement: 'top'
    });

    // 自定义工具提示（长文本显示）
    $('.tooltip-cell').hover(function(e) {
        const title = $(this).attr('title');
        if (title && title.length > 0) {
            $(this).tooltip({
                title: title,
                placement: 'top',
                trigger: 'manual'
            }).tooltip('show');
        }
    }, function() {
        $(this).tooltip('dispose');
    });

    // 排序功能
    $('.btn-sort').click(function() {
        const order = $(this).data('order');
        const $form = $(this).closest('form');
        
        // 更新隐藏字段
        $form.find('input[name="days_since_last_track_order"]').val(order);
        
        // 按钮状态切换
        $('.btn-sort').removeClass('active');
        $(this).addClass('active');
        
        // 提交表单
        $form.submit();
    });

    // 分页链接保持排序参数
    $('.pagination').on('click', 'a', function(e) {
        e.preventDefault();
        const order = $('input[name="days_since_last_track_order"]').val();
        const newUrl = $(this).attr('href') + (order ? '&days_since_last_track_order=' + order : '');
        window.location.href = newUrl;
    });

    // Excel导出
    $('#exportExcel').click(function() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'excel');
        window.location.href = 'export.php?' + params.toString();
    });
});
</script>

<?php 
// 辅助函数
function getTrackDaysColor($days) {
    if ($days > 7) return 'danger';
    if ($days > 3) return 'warning';
    return 'success';
}

function truncateText($text, $length) {
    return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '...' : $text;
}

require 'views/footer.php';
?>