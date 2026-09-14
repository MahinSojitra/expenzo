<?php
$module = 'budgets';
$heading = 'Budgets';
$subtitle = is_adminish() ? 'Review user budget usage.' : 'Track spending against your limits.';
$actionUrl = can('budgets.create') ? 'budgets/create' : null;
$actionLabel = '+ Create Budget';
require dirname(__DIR__).'/partials/page-header.php';
?>

<div class="card"><div class="card-body">
<div class="table-responsive" role="region" aria-label="Budgets table" tabindex="0"><table class="table crud-table mb-0">
<thead><tr><?php if (is_adminish()): ?><th scope="col">User</th><?php endif; ?><th scope="col">Period</th><th scope="col">Category</th><th scope="col">Budget</th><th scope="col">Spent</th><th scope="col">Remaining</th><th scope="col">Usage</th><th scope="col">Status</th><th scope="col" class="text-end">Actions</th></tr></thead>
<tbody>
<?php if (!$rows): ?><tr><td colspan="<?=is_adminish() ? 9 : 8?>" class="text-center py-5"><p class="text-muted">No budgets found.</p><?php if ($actionUrl): ?><a class="action-button action-button--success btn btn-outline-primary" href="<?=e(url($actionUrl))?>"><i data-feather="plus-circle" aria-hidden="true"></i><?=e(ltrim($actionLabel, "+ "))?></a><?php endif; ?></td></tr><?php endif; ?>
<?php foreach ($rows as $r):
$pct = $r['budget_amount'] > 0 ? ($r['spent'] / $r['budget_amount']) * 100 : 0;
$warningThreshold = (float)($r['warning_threshold'] ?? 80);
$isLimitReached = $pct >= 100;
$isWarning = !$isLimitReached && $pct >= $warningThreshold;
$remaining = $r['budget_amount'] - $r['spent'];
?>
<tr>
<?php if (is_adminish()): ?><td><?=e($r['user_name'] ?? '')?></td><?php endif; ?>
<td class="text-nowrap"><?=e(display_date($r['start_date']))?><br><small class="text-muted">to <?=e(display_date($r['end_date']))?></small></td>
<td><?=category_label($r['category_name'] ?? 'All Categories', $r['category_icon'] ?? ($r['category_name'] ? 'tag' : 'layers'), $r['category_color'] ?? null, $r['badge'] ?? null)?></td>
<td class="text-nowrap"><?=money($r['budget_amount'])?></td><td class="text-nowrap"><?=money($r['spent'])?></td><td class="text-nowrap <?=$remaining < 0 ? 'text-danger' : ''?>"><?=money($remaining)?></td>
<td class="budget-usage"><div class="progress" role="progressbar" aria-label="Budget usage" aria-valuenow="<?=max(0, min(100, $pct))?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar <?=$isLimitReached ? 'bg-danger' : ($isWarning ? 'bg-warning' : '')?>" style="width:<?=max(0, min(100, $pct))?>%"></div></div><div class="budget-usage-meta"><small><?=number_format($pct, 1)?>%</small><?php if ($isLimitReached): ?><span class="status-badge status-badge--danger"><i data-feather="alert-octagon" aria-hidden="true"></i>Limit reached</span><?php elseif ($isWarning): ?><span class="status-badge status-badge--warning"><i data-feather="alert-triangle" aria-hidden="true"></i>Warning</span><?php endif; ?></div></td>
<td><span class="status-badge status-badge--<?=in_array($r['status'], ['active', 'posted'], true) ? 'success' : 'neutral'?>"><i data-feather="<?=in_array($r['status'], ['active', 'posted'], true) ? 'check-circle' : 'pause-circle'?>" aria-hidden="true"></i><?=e(ucfirst($r['status']))?></span></td>
<td><?php $rowId = $r['id']; $rowName = 'budget'; $owned = (int)$r['user_id'] === (int)\App\Core\Session::get('user_id'); $mayEdit = $owned && can('budgets.edit'); $mayDelete = $owned && can('budgets.delete'); require dirname(__DIR__).'/partials/row-actions.php'; ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php $total = $data['total']; $page = $data['page']; $per = $data['per']; require dirname(__DIR__).'/partials/pagination.php'; ?>
</div></div>
