<?php
$filters = $filters ?? ($f ?? []);
$summary = $analytics['summary'] ?? ['total' => 0, 'count' => count($rows), 'average' => 0, 'highest' => 0];
$query = http_build_query($filters);
$canAnalytics = can('reports.analytics');
$canCustomize = can('reports.customize');
$canExportCsv = can('reports.export_csv') || can('reports.export');
$canExportVisuals = can('reports.export_visuals') || can('reports.export');
$chartType = $canCustomize ? ($filters['chart_type'] ?? 'bar') : 'bar';
if (!in_array($chartType, ['bar', 'line'], true)) $chartType = 'bar';
$primaryBreakdown = $canCustomize ? ($filters['breakdown'] ?? 'category') : 'category';
if (!in_array($primaryBreakdown, ['category', 'account', 'user'], true)) $primaryBreakdown = 'category';
$breakdownRows = match ($primaryBreakdown) {
    'account' => $analytics['byAccount'] ?? [],
    'user' => $analytics['byUser'] ?? [],
    default => $analytics['byCategory'] ?? [],
};
$breakdownTitle = match ($primaryBreakdown) {
    'account' => 'Expenses by Account',
    'user' => 'Expenses by User',
    default => 'Expenses by Category',
};
?>
<div class="page-header">
    <div><h1 class="h3">Reports</h1><p class="text-muted mb-0">Analyze spending patterns and export filtered results.</p></div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if($canExportCsv): ?><a class="action-button action-button--primary btn btn-outline-primary" href="<?=url('reports/csv?'.$query)?>"><i data-feather="download" aria-hidden="true"></i>Export expenses</a><?php endif;?>
        <?php if($canExportCsv && $canAnalytics): ?><a class="action-button action-button--success btn btn-outline-primary" href="<?=url('reports/insights.csv?'.$query)?>"><i data-feather="file-text" aria-hidden="true"></i>Export insights</a><?php endif;?>
    </div>
</div>
<div class="card report-panel"><div class="card-body">
<form class="report-filters">
    <div><label class="form-label" for="report-search">Search</label><div class="input-group search-input-group"><span class="input-group-text"><i data-feather="search" aria-hidden="true"></i></span><input id="report-search" class="form-control" name="q" value="<?=e($filters['q'] ?? '')?>" placeholder="Description, notes or user"></div></div>
    <div><label class="form-label" for="report-category">Category</label><select id="report-category" class="form-select" name="category_id"><option value="" data-icon="layers">All Categories</option><?php foreach($categories as $c):?><option value="<?=$c['id']?>" data-icon="<?=e($c['display_icon'] ?? $c['icon'] ?? 'tag')?>" <?=selected($filters['category_id']??'',$c['id'])?>><?=e($c['name'])?></option><?php endforeach;?></select></div>
    <div><label class="form-label" for="report-account">Account</label><select id="report-account" class="form-select" name="account_id"><option value="" data-icon="briefcase">All Accounts</option><?php foreach($accounts as $a):?><option value="<?=$a['id']?>" data-account-type="<?=e($a['type'] ?? '')?>" <?=selected($filters['account_id']??'',$a['id'])?>><?=e($a['name'])?></option><?php endforeach;?></select></div>
    <?php if (can('finance.view_all')): ?><div><label class="form-label" for="report-user">User</label><select id="report-user" class="form-select" name="user_id"><option value="" data-icon="users">All users</option><?php foreach($users as $user):?><option value="<?=e($user['id'])?>" data-icon="user" <?=selected($filters['user_id']??'',$user['id'])?>><?=e($user['name'])?></option><?php endforeach;?></select></div><?php endif; ?>
    <div><label class="form-label" for="report-from">From date</label><input id="report-from" class="form-control" type="date" name="from" value="<?=e($filters['from']??'')?>"></div>
    <div><label class="form-label" for="report-to">To date</label><input id="report-to" class="form-control" type="date" name="to" value="<?=e($filters['to']??'')?>"></div>
    <?php if ($canCustomize): ?><div><label class="form-label" for="report-breakdown">Breakdown</label><select id="report-breakdown" class="form-select" name="breakdown"><option value="category" data-icon="layers" <?=selected($primaryBreakdown,'category')?>>Category</option><option value="account" data-icon="briefcase" <?=selected($primaryBreakdown,'account')?>>Account</option><?php if(can('finance.view_all')):?><option value="user" data-icon="users" <?=selected($primaryBreakdown,'user')?>>User</option><?php endif;?></select></div><div><label class="form-label" for="report-chart-type">Trend style</label><select id="report-chart-type" class="form-select" name="chart_type"><option value="bar" data-icon="bar-chart-2" <?=selected($chartType,'bar')?>>Bar chart</option><option value="line" data-icon="trending-up" <?=selected($chartType,'line')?>>Line chart</option></select></div><?php endif; ?>
    <div class="report-filter-actions"><button class="action-button action-button--primary btn btn-primary" type="submit"><i data-feather="filter" aria-hidden="true"></i>Apply</button><a class="action-button action-button--neutral btn btn-outline-secondary" href="<?=e(url('reports'))?>"><i data-feather="rotate-ccw" aria-hidden="true"></i>Reset</a></div>
</form>
</div></div>
<?php if($canAnalytics): ?>
<div class="row report-summary">
    <div class="col-xl-3 col-sm-6"><div class="card dashboard-stat-card"><div class="card-body"><div><div class="text-muted">Total spent</div><div class="stat-value"><?=money($summary['total'])?></div></div><span class="dashboard-stat-icon dashboard-stat-icon--danger"><i data-feather="credit-card" aria-hidden="true"></i></span></div></div></div>
    <div class="col-xl-3 col-sm-6"><div class="card dashboard-stat-card"><div class="card-body"><div><div class="text-muted">Transactions</div><div class="stat-value"><?=e($summary['count'])?></div></div><span class="dashboard-stat-icon dashboard-stat-icon--primary"><i data-feather="list" aria-hidden="true"></i></span></div></div></div>
    <div class="col-xl-3 col-sm-6"><div class="card dashboard-stat-card"><div class="card-body"><div><div class="text-muted">Average expense</div><div class="stat-value"><?=money($summary['average'])?></div></div><span class="dashboard-stat-icon dashboard-stat-icon--info"><i data-feather="activity" aria-hidden="true"></i></span></div></div></div>
    <div class="col-xl-3 col-sm-6"><div class="card dashboard-stat-card"><div class="card-body"><div><div class="text-muted">Highest expense</div><div class="stat-value"><?=money($summary['highest'])?></div></div><span class="dashboard-stat-icon dashboard-stat-icon--warning"><i data-feather="trending-up" aria-hidden="true"></i></span></div></div></div>
</div>
<div class="row">
    <div class="col-xl-8"><div class="card"><div class="card-header report-card-header"><h5 class="card-title mb-0">Monthly trend</h5><?php if($canExportVisuals): ?><button class="action-button action-button--primary btn btn-sm btn-outline-primary" type="button" data-chart-download="reportTrendChart" data-filename="monthly-trend.png"><i data-feather="image" aria-hidden="true"></i>Export chart</button><?php endif;?></div><div class="card-body"><?php if(!empty($analytics['monthly'])):?><div class="dashboard-chart"><canvas id="reportTrendChart"></canvas></div><?php else:?><div class="dashboard-chart-empty"><i data-feather="bar-chart-2" aria-hidden="true"></i><p>No data available</p></div><?php endif;?></div></div></div>
    <div class="col-xl-4"><div class="card"><div class="card-header report-card-header"><h5 class="card-title mb-0"><?=e($breakdownTitle)?></h5><?php if($canExportVisuals): ?><button class="action-button action-button--primary btn btn-sm btn-outline-primary" type="button" data-chart-download="reportBreakdownChart" data-filename="expense-breakdown.png"><i data-feather="image" aria-hidden="true"></i>Export chart</button><?php endif;?></div><div class="card-body"><?php if(!empty($breakdownRows)):?><div class="dashboard-chart"><canvas id="reportBreakdownChart"></canvas></div><?php else:?><div class="dashboard-chart-empty"><i data-feather="pie-chart" aria-hidden="true"></i><p>No data available</p></div><?php endif;?></div></div></div>
</div>
<?php if(!empty($analytics['insights'])): ?><div class="card"><div class="card-body"><h5 class="card-title">Key insights</h5><div class="report-insights"><?php foreach($analytics['insights'] as $insight): ?><div class="report-insight"><span class="report-insight-icon"><i data-feather="zap" aria-hidden="true"></i></span><div><strong><?=e($insight['label'])?></strong><span class="report-insight-value"><?=e($insight['value'])?></span></div></div><?php endforeach; ?></div></div></div><?php endif; ?>
<?php endif; ?>
<div class="card"><div class="card-body">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><h5 class="card-title mb-0">Expense details</h5><span class="text-muted small"><?=e(number_format(count($rows)))?> matching rows</span></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Date</th><th>Amount</th><th>Category</th><th>Account</th><?php if(can('finance.view_all')):?><th>User</th><?php endif; ?><th>Description</th></tr></thead><tbody><?php foreach($rows as $row):?><tr><td><?=e(display_date($row['expense_date']))?></td><td class="fw-semibold"><?=money($row['amount'])?></td><td><?=category_label($row['category'] ?? '', $row['category_icon'] ?? null, $row['category_color'] ?? null, $row['badge'] ?? null)?></td><td><?=account_type_label($row['account_type'] ?? '', $row['account'] ?? null)?></td><?php if(can('finance.view_all')):?><td><?=e($row['user_name'] ?? '')?></td><?php endif; ?><td><?=e($row['description'])?></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="<?=can('finance.view_all') ? 6 : 5?>" class="text-center text-muted py-5"><i data-feather="inbox" aria-hidden="true"></i><p class="mb-0">No expenses match these filters.</p></td></tr><?php endif;?></tbody></table></div>
</div></div>
<?php if($canAnalytics): ob_start(); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const monthly = <?=json_encode($analytics['monthly'] ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
    const breakdown = <?=json_encode($breakdownRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
    const chartType = <?=json_encode($chartType)?>;
    if (typeof Chart === 'undefined') return;
    const legacy = !!Chart.defaults.global;
    const palette = ['#3b7ddd','#16a085','#8b5cf6','#f59e0b','#e45470','#06b6d4','#64748b','#a85577'];
    const trendCanvas = document.getElementById('reportTrendChart');
    if (trendCanvas && monthly.length) {
        new Chart(trendCanvas, {type: chartType, data: {labels: monthly.map(item => item.label), datasets: [{label: 'Expenses', data: monthly.map(item => Number(item.value)), backgroundColor: '#3b7ddd', borderColor: '#2459a6', borderWidth: 2, fill: chartType === 'bar'}]}, options: {responsive: true, maintainAspectRatio: false, ...(legacy ? {legend: {display: false}, scales: {yAxes: [{ticks: {beginAtZero: true}}], xAxes: [{gridLines: {display: false}}]}} : {plugins: {legend: {display: false}}, scales: {y: {beginAtZero: true}, x: {grid: {display: false}}}})}});
    }
    const breakdownCanvas = document.getElementById('reportBreakdownChart');
    if (breakdownCanvas && breakdown.length) {
        new Chart(breakdownCanvas, {type: 'doughnut', data: {labels: breakdown.map(item => item.label), datasets: [{data: breakdown.map(item => Number(item.value)), backgroundColor: palette, borderColor: '#ffffff', borderWidth: 2}]}, options: {responsive: true, maintainAspectRatio: false, ...(legacy ? {legend: {position: 'bottom'}, cutoutPercentage: 65} : {plugins: {legend: {position: 'bottom'}}, cutout: '65%'})}});
    }
    document.querySelectorAll('[data-chart-download]').forEach(function (button) {
        button.addEventListener('click', function () {
            const canvas = document.getElementById(button.dataset.chartDownload);
            if (!canvas) return;
            const link = document.createElement('a');
            link.download = button.dataset.filename || 'chart.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    });
});
</script>
<?php $scripts = ($scripts ?? '') . ob_get_clean(); endif; ?>
