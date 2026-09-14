<div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h3 mb-1">Dashboard</h1><p class="text-muted mb-0"><?=!empty($stats['admin'])?'System expense overview.':'Your real-time expense overview.'?></p></div><?php if(can('expenses.create')):?><a href="<?=url('expenses/create')?>" class="action-button action-button--success btn btn-primary"><i data-feather="plus-circle" aria-hidden="true"></i>Add Expense</a><?php endif;?></div>
<?php if(!empty($stats['admin'])): ?>
<div class="row"><div class="col-xl-2 col-sm-6"><div class="card dashboard-stat-card"><div class="card-body"><div><div class="text-muted">Total Users</div><div class="stat-value"><?=e($stats['totalUsers'])?></div></div><span class="dashboard-stat-icon dashboard-stat-icon--primary"><i data-feather="users" aria-hidden="true"></i></span></div></div></div><div class="col-xl-2 col-sm-6"><div class="card dashboard-stat-card"><div class="card-body"><div><div class="text-muted">Active Users</div><div class="stat-value"><?=e($stats['activeUsers'])?></div></div><span class="dashboard-stat-icon dashboard-stat-icon--success"><i data-feather="user-check" aria-hidden="true"></i></span></div></div></div><div class="col-xl-2 col-sm-6"><div class="card dashboard-stat-card"><div class="card-body"><div><div class="text-muted">Total Expenses</div><div class="stat-value"><?=money($stats['total'])?></div></div><span class="dashboard-stat-icon dashboard-stat-icon--danger"><i data-feather="credit-card" aria-hidden="true"></i></span></div></div></div><div class="col-xl-2 col-sm-6"><div class="card dashboard-stat-card"><div class="card-body"><div><div class="text-muted">This Month</div><div class="stat-value"><?=money($stats['month'])?></div></div><span class="dashboard-stat-icon dashboard-stat-icon--info"><i data-feather="calendar" aria-hidden="true"></i></span></div></div></div><div class="col-xl-2 col-sm-6"><div class="card dashboard-stat-card"><div class="card-body"><div><div class="text-muted">Categories</div><div class="stat-value"><?=e($stats['categories'])?></div></div><span class="dashboard-stat-icon dashboard-stat-icon--purple"><i data-feather="layers" aria-hidden="true"></i></span></div></div></div><div class="col-xl-2 col-sm-6"><div class="card dashboard-stat-card"><div class="card-body"><div><div class="text-muted">Accounts</div><div class="stat-value"><?=e($stats['accounts'])?></div></div><span class="dashboard-stat-icon dashboard-stat-icon--warning"><i data-feather="briefcase" aria-hidden="true"></i></span></div></div></div></div>
<div class="row"><div class="col-xl-8"><div class="card"><div class="card-header"><h5 class="card-title mb-0">Monthly Expenses</h5></div><div class="card-body"><?php if (!empty($stats['monthly'])): ?><div class="dashboard-chart"><canvas id="monthlyChart" role="img" aria-label="Monthly expense totals"></canvas></div><?php else: ?><div class="dashboard-chart-empty"><i data-feather="bar-chart-2" aria-hidden="true"></i><p>No data available</p></div><?php endif; ?></div></div></div><div class="col-xl-4"><div class="card"><div class="card-header"><h5 class="card-title mb-0">Expenses by Category</h5></div><div class="card-body"><?php if (!empty($stats['byCategory'])): ?><div class="dashboard-chart"><canvas id="categoryChart" role="img" aria-label="Expenses by category"></canvas></div><?php else: ?><div class="dashboard-chart-empty"><i data-feather="pie-chart" aria-hidden="true"></i><p>No data available</p></div><?php endif; ?></div></div></div></div>
<div class="row"><div class="col-xl-6"><div class="card"><div class="card-header"><h5 class="card-title mb-0">Expenses by User</h5></div><div class="card-body"><?php if (!empty($stats['byUser'])): ?><div class="dashboard-chart dashboard-chart-sm"><canvas id="userChart" role="img" aria-label="Expense totals by user"></canvas></div><?php else: ?><div class="dashboard-chart-empty dashboard-chart-empty-sm"><i data-feather="users" aria-hidden="true"></i><p>No data available</p></div><?php endif; ?></div></div></div><div class="col-xl-6"><div class="card"><div class="card-header"><h5 class="card-title mb-0">Expenses by Account Type</h5></div><div class="card-body"><?php if (!empty($stats['byAccountType'])): ?><div class="dashboard-chart dashboard-chart-sm"><canvas id="accountTypeChart" role="img" aria-label="Expense totals by account type"></canvas></div><?php else: ?><div class="dashboard-chart-empty dashboard-chart-empty-sm"><i data-feather="briefcase" aria-hidden="true"></i><p>No data available</p></div><?php endif; ?></div></div></div></div>
<?php else: ?>
<?php if(!empty($stats['budgetAlerts'])): ?>
<div class="dashboard-budget-alerts mb-3">
<?php foreach($stats['budgetAlerts'] as $alert): ?>
<div class="alert <?=$alert['limitReached'] ? 'alert-danger' : 'alert-warning'?> alert-dismissible fade show budget-alert" role="alert">
<div class="budget-alert-icon"><i data-feather="<?=$alert['limitReached'] ? 'alert-octagon' : 'alert-triangle'?>"></i></div>
<div class="budget-alert-content">
<strong><?=$alert['limitReached'] ? 'Budget limit reached' : 'Budget warning'?></strong>
<div><?=e($alert['category'])?> has used <?=number_format($alert['percent'], 1)?>% of the budget. Spent <?=money($alert['spent'])?> of <?=money($alert['amount'])?><?php if($alert['remaining'] >= 0): ?>, <?=money($alert['remaining'])?> remaining<?php else: ?>, <?=money(abs($alert['remaining']))?> over limit<?php endif; ?>.</div>
</div>
<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss budget warning"></button>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<div class="row"><div class="col-xl-3 col-sm-6"><div class="card dashboard-stat-card"><div class="card-body"><div><div class="text-muted">Total Expenses</div><div class="stat-value"><?=money($stats['total'])?></div></div><span class="dashboard-stat-icon dashboard-stat-icon--danger"><i data-feather="credit-card" aria-hidden="true"></i></span></div></div></div><div class="col-xl-3 col-sm-6"><div class="card dashboard-stat-card"><div class="card-body"><div><div class="text-muted">This Month</div><div class="stat-value"><?=money($stats['month'])?></div></div><span class="dashboard-stat-icon dashboard-stat-icon--info"><i data-feather="calendar" aria-hidden="true"></i></span></div></div></div><div class="col-xl-3 col-sm-6"><div class="card dashboard-stat-card"><div class="card-body"><div><div class="text-muted">Today</div><div class="stat-value"><?=money($stats['today'])?></div></div><span class="dashboard-stat-icon dashboard-stat-icon--primary"><i data-feather="clock" aria-hidden="true"></i></span></div></div></div><div class="col-xl-3 col-sm-6"><div class="card dashboard-stat-card"><div class="card-body"><div><div class="text-muted">Account Balance</div><div class="stat-value"><?=money($stats['balance'])?></div></div><span class="dashboard-stat-icon dashboard-stat-icon--success"><i data-feather="briefcase" aria-hidden="true"></i></span></div></div></div></div>
<div class="row"><div class="col-xl-8"><div class="card"><div class="card-header"><h5 class="card-title mb-0">Monthly Expenses</h5></div><div class="card-body"><?php if (!empty($stats['monthly'])): ?><div class="dashboard-chart"><canvas id="monthlyChart" role="img" aria-label="Monthly expense totals"></canvas></div><?php else: ?><div class="dashboard-chart-empty"><i data-feather="bar-chart-2" aria-hidden="true"></i><p>No data available</p></div><?php endif; ?></div></div></div><div class="col-xl-4"><div class="card"><div class="card-header"><h5 class="card-title mb-0">Expenses by Category</h5></div><div class="card-body"><?php if (!empty($stats['byCategory'])): ?><div class="dashboard-chart"><canvas id="categoryChart" role="img" aria-label="Expenses by category"></canvas></div><?php else: ?><div class="dashboard-chart-empty"><i data-feather="pie-chart" aria-hidden="true"></i><p>No data available</p></div><?php endif; ?></div></div></div></div>
<div class="row"><div class="col-md-6"><div class="card"><div class="card-body"><div class="d-flex justify-content-between"><div><div class="text-muted">Current Budget</div><div class="h4 mb-0"><?=money($stats['budget'])?></div></div><div class="text-end"><div class="text-muted">Remaining</div><div class="h4 mb-0 <?=($stats['remaining']<0?'text-danger':'')?>"><?=money($stats['remaining'])?></div></div></div><?php $pct=$stats['budget']>0?min(100,max(0,($stats['month']/$stats['budget'])*100)):0; $hasLimitAlert=!empty(array_filter($stats['budgetAlerts'] ?? [], fn($alert) => !empty($alert['limitReached']))); $hasBudgetAlert=!empty($stats['budgetAlerts']); ?><div class="progress mt-3" style="height:8px"><div class="progress-bar <?=$hasLimitAlert ? 'bg-danger' : ($hasBudgetAlert ? 'bg-warning' : '')?>" style="width:<?=$pct?>%"></div></div></div></div></div></div>
<?php endif; ?>
<?php ob_start(); ?>
<script>
(function () {
    const monthly = <?=json_encode($stats['monthly'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
    const categories = <?=json_encode($stats['byCategory'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
    const users = <?=json_encode($stats['byUser'] ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
    const accountTypes = <?=json_encode($stats['byAccountType'] ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
    const legacy = !!Chart.defaults.global;
    const palette = ['#3b7ddd','#16a085','#8b5cf6','#f59e0b','#e45470','#06b6d4','#64748b','#a85577'];
    const monthlyCanvas = document.getElementById('monthlyChart');
    if (monthlyCanvas && monthly.length) {
        const legend = {display:false};
        new Chart(monthlyCanvas, {
            type:'bar',
            data:{
                labels:monthly.map(item => item.label),
                datasets:[{
                    label:'Expenses', data:monthly.map(item => Number(item.value)),
                    backgroundColor:'#3b7ddd', hoverBackgroundColor:'#2459a6',
                    borderColor:'#2459a6', borderWidth:1, maxBarThickness:56
                }]
            },
            options:{
                responsive:true, maintainAspectRatio:false,
                ...(legacy ? {
                    legend:legend,
                    scales:{yAxes:[{ticks:{beginAtZero:true}}], xAxes:[{gridLines:{display:false}}]}
                } : {
                    plugins:{legend:legend},
                    scales:{y:{beginAtZero:true}, x:{grid:{display:false}}}
                })
            }
        });
    }
    const categoryCanvas = document.getElementById('categoryChart');
    if (categoryCanvas && categories.length) {
        const legend = {position:'bottom', labels:{boxWidth:12, padding:16}};
        new Chart(categoryCanvas, {
            type:'doughnut',
            data:{
                labels:categories.map(item => item.label),
                datasets:[{
                    data:categories.map(item => Number(item.value)),
                    backgroundColor:palette,
                    borderColor:'#ffffff', borderWidth:2
                }]
            },
            options:{
                responsive:true, maintainAspectRatio:false,
                ...(legacy ? {legend:legend, cutoutPercentage:65} : {plugins:{legend:legend}, cutout:'65%'})
            }
        });
    }
    function horizontalBar(canvasId, rows, label) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || !rows.length) return;
        const legend = {display:false};
        new Chart(canvas, {
            type: legacy ? 'horizontalBar' : 'bar',
            data:{labels:rows.map(item => item.label), datasets:[{label:label, data:rows.map(item => Number(item.value)), backgroundColor:palette, borderColor:'#ffffff', borderWidth:1}]},
            options:{
                responsive:true, maintainAspectRatio:false, indexAxis:'y',
                ...(legacy ? {legend:legend, scales:{xAxes:[{ticks:{beginAtZero:true}}], yAxes:[{gridLines:{display:false}}]}} : {plugins:{legend:legend}, scales:{x:{beginAtZero:true}, y:{grid:{display:false}}}})
            }
        });
    }
    horizontalBar('userChart', users, 'Expenses');
    horizontalBar('accountTypeChart', accountTypes, 'Expenses');
})();
</script>
<?php $scripts = ($scripts ?? '') . ob_get_clean(); ?>

