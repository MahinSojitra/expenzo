<div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h3 mb-1">Dashboard</h1><p class="text-muted mb-0"><?=!empty($stats['admin'])?'System expense overview.':'Your real-time expense overview.'?></p></div><?php if(can('expenses.create')):?><a href="<?=url('expenses/create')?>" class="action-button action-button--success btn btn-primary"><i data-feather="plus-circle" aria-hidden="true"></i>Add Expense</a><?php endif;?></div>
<?php if(!empty($stats['admin'])): ?>
<?php if(!empty($stats['budgetAlerts'])): ?>
<div class="dashboard-budget-alerts mb-3">
<?php foreach($stats['budgetAlerts'] as $alert): ?>
<div class="alert <?=$alert['limitReached'] ? 'alert-danger' : 'alert-warning'?> alert-dismissible fade show budget-alert" role="alert">
<div class="budget-alert-icon"><i data-feather="<?=$alert['limitReached'] ? 'alert-octagon' : 'alert-triangle'?>"></i></div>
<div class="budget-alert-content">
<strong><?=$alert['limitReached'] ? 'Budget limit reached' : 'Budget warning'?></strong>
<div><?=e($alert['user'])?> - <?=e($alert['category'])?> has used <?=number_format($alert['percent'], 1)?>% of the budget. Spent <?=money($alert['spent'])?> of <?=money($alert['amount'])?><?php if($alert['remaining'] >= 0): ?>, <?=money($alert['remaining'])?> remaining<?php else: ?>, <?=money(abs($alert['remaining']))?> over limit<?php endif; ?>.</div>
</div>
<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss budget warning"></button>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<div class="row"><div class="col-xl-2 col-sm-6"><div class="card"><div class="card-body"><div class="text-muted">Total Users</div><div class="stat-value"><?=e($stats['totalUsers'])?></div></div></div></div><div class="col-xl-2 col-sm-6"><div class="card"><div class="card-body"><div class="text-muted">Active Users</div><div class="stat-value"><?=e($stats['activeUsers'])?></div></div></div></div><div class="col-xl-2 col-sm-6"><div class="card"><div class="card-body"><div class="text-muted">Total Expenses</div><div class="stat-value"><?=money($stats['total'])?></div></div></div></div><div class="col-xl-2 col-sm-6"><div class="card"><div class="card-body"><div class="text-muted">This Month</div><div class="stat-value"><?=money($stats['month'])?></div></div></div></div><div class="col-xl-2 col-sm-6"><div class="card"><div class="card-body"><div class="text-muted">Categories</div><div class="stat-value"><?=e($stats['categories'])?></div></div></div></div><div class="col-xl-2 col-sm-6"><div class="card"><div class="card-body"><div class="text-muted">Accounts</div><div class="stat-value"><?=e($stats['accounts'])?></div></div></div></div></div>
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
<div class="row"><div class="col-xl-3 col-sm-6"><div class="card"><div class="card-body"><div class="text-muted">Total Expenses</div><div class="stat-value"><?=money($stats['total'])?></div></div></div></div><div class="col-xl-3 col-sm-6"><div class="card"><div class="card-body"><div class="text-muted">This Month</div><div class="stat-value"><?=money($stats['month'])?></div></div></div></div><div class="col-xl-3 col-sm-6"><div class="card"><div class="card-body"><div class="text-muted">Today</div><div class="stat-value"><?=money($stats['today'])?></div></div></div></div><div class="col-xl-3 col-sm-6"><div class="card"><div class="card-body"><div class="text-muted">Account Balance</div><div class="stat-value"><?=money($stats['balance'])?></div></div></div></div></div>
<div class="row"><div class="col-xl-8"><div class="card"><div class="card-header"><h5 class="card-title mb-0">Monthly Expenses</h5></div><div class="card-body"><canvas id="monthlyChart" height="120"></canvas></div></div></div><div class="col-xl-4"><div class="card"><div class="card-header"><h5 class="card-title mb-0">Expenses by Category</h5></div><div class="card-body"><canvas id="categoryChart" height="250"></canvas></div></div></div></div>
<div class="row"><div class="col-md-6"><div class="card"><div class="card-body"><div class="d-flex justify-content-between"><div><div class="text-muted">Current Budget</div><div class="h4 mb-0"><?=money($stats['budget'])?></div></div><div class="text-end"><div class="text-muted">Remaining</div><div class="h4 mb-0 <?=($stats['remaining']<0?'text-danger':'')?>"><?=money($stats['remaining'])?></div></div></div><?php $pct=$stats['budget']>0?min(100,max(0,($stats['month']/$stats['budget'])*100)):0; $hasLimitAlert=!empty(array_filter($stats['budgetAlerts'] ?? [], fn($alert) => !empty($alert['limitReached']))); $hasBudgetAlert=!empty($stats['budgetAlerts']); ?><div class="progress mt-3" style="height:8px"><div class="progress-bar <?=$hasLimitAlert ? 'bg-danger' : ($hasBudgetAlert ? 'bg-warning' : '')?>" style="width:<?=$pct?>%"></div></div></div></div></div></div>
<?php $scripts='<script>const monthly='.json_encode($stats['monthly']).'; const cats='.json_encode($stats['byCategory']).'; new Chart(document.getElementById("monthlyChart"),{type:"line",data:{labels:monthly.map(x=>x.label),datasets:[{label:"Expenses",data:monthly.map(x=>Number(x.value)),fill:false}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}}); new Chart(document.getElementById("categoryChart"),{type:"doughnut",data:{labels:cats.map(x=>x.label),datasets:[{data:cats.map(x=>Number(x.value))}]},options:{responsive:true,plugins:{legend:{position:"bottom"}}}});</script>'; ?>
<?php endif; ?>

