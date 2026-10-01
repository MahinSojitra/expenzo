<?php
$heading = 'Transactions';
$subtitle = 'Income, transfers, balance adjustments and expenses. Opening balances are shown on Accounts.';
$actionUrl = can('transactions.create') ? 'transactions/create' : (can('transactions.adjust') ? 'transactions/create?type=adjustment' : null);
$actionLabel = '+ Add Transaction';
require dirname(__DIR__).'/partials/page-header.php';
?>
<div class="card"><div class="card-body">
<form method="get" action="<?=e(url('transactions'))?>" class="row g-2 mb-3">
<div class="col-md-6 col-xl-3"><label for="account-filter" class="form-label">Account</label><select id="account-filter" name="account_id" class="form-select"><option value="">All accounts</option>
<?php foreach ($accounts as $account): ?><option value="<?=e($account['id'])?>" data-icon="<?=e(account_type_icon($account['type'] ?? ''))?>" data-subtitle="<?=e(is_adminish() ? ($account['user_name'] ?? '') : '')?>" <?=selected($accountId,$account['id'])?>><?=e($account['name'])?></option><?php endforeach; ?>
</select></div>
<div class="col-md-6 col-xl-3"><label for="transaction-search" class="form-label">Search</label><div class="input-group search-input-group"><span class="input-group-text"><i data-feather="search" aria-hidden="true"></i></span><input id="transaction-search" name="q" class="form-control" value="<?=e($filters['q'])?>" maxlength="255" placeholder="Description or reversal reason"></div></div>
<div class="col-md-6 col-xl-3"><label for="transaction-type" class="form-label">Transaction type</label><select id="transaction-type" name="type" class="form-select"><option value="" data-icon="list">All types</option>
<?php foreach (['income'=>['Income','trending-up'],'transfer'=>['Transfer','repeat'],'adjustment'=>['Balance adjustment','sliders'],'expense'=>['Expense','credit-card']] as $value=>[$label,$icon]): ?><option value="<?=e($value)?>" data-icon="<?=e($icon)?>" <?=selected($filters['type'],$value)?>><?=e($label)?></option><?php endforeach; ?>
</select></div>
<div class="col-md-6 col-xl-3"><label for="transaction-status" class="form-label">Status</label><select id="transaction-status" name="status" class="form-select"><option value="" data-icon="list">All statuses</option>
<?php foreach (['posted'=>'check-circle','reversed'=>'rotate-ccw','draft'=>'edit-3','void'=>'x-circle'] as $value=>$icon): ?><option value="<?=e($value)?>" data-icon="<?=e($icon)?>" <?=selected($filters['status'],$value)?>><?=e(ucfirst($value))?></option><?php endforeach; ?>
</select></div>
<?php foreach (['from'=>'From date','to'=>'To date'] as $key=>$label): ?>
<div class="col-md-6 col-xl-3"><label for="transaction-<?=e($key)?>" class="form-label"><?=e($label)?></label><input id="transaction-<?=e($key)?>" type="date" name="<?=e($key)?>" value="<?=e($filters[$key])?>" class="form-control"></div>
<?php endforeach; ?>
<div class="col-md-6 d-flex align-items-end gap-2"><button class="action-button action-button--primary btn btn-outline-primary" type="submit"><i data-feather="filter" aria-hidden="true"></i>Filter</button><a class="action-button action-button--neutral btn btn-outline-secondary" href="<?=e(url('transactions'))?>"><i data-feather="x" aria-hidden="true"></i>Reset</a></div>
<?php if ($errors): ?><div class="col-12" role="alert"><?php foreach ($errors as $error): ?><p class="text-danger mb-1"><?=e($error)?></p><?php endforeach; ?></div><?php endif; ?>

</form>

<div class="table-responsive" role="region" aria-label="Transaction history" tabindex="0">
<table class="table crud-table mb-0"><thead><tr><th>Date</th><?php if (is_adminish()): ?><th>User</th><?php endif; ?><th>Type</th><th>Account</th><th>Description</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if (!$data['rows']): ?><tr><td colspan="<?=is_adminish()?8:7?>" class="text-center py-5 text-muted">No transactions found.</td></tr><?php endif; ?>
<?php foreach ($data['rows'] as $row): ?>
<tr><td class="text-nowrap"><?=e(display_date($row['transaction_date']))?></td>
<?php if (is_adminish()): ?><td><?=e($row['user_name'])?></td><?php endif; ?>
<td><span class="inline-icon-text"><i data-feather="<?=e(['income'=>'trending-up','transfer'=>'repeat','adjustment'=>'sliders','expense'=>'credit-card'][$row['type']] ?? 'list')?>" aria-hidden="true"></i><?=e(ucfirst($row['type']))?></span></td>
<td><?=account_type_label($row['account_type'] ?? '', $row['account_name'])?><?php if ($row['destination_name']): ?> &rarr; <?=account_type_label($row['destination_type'] ?? '', $row['destination_name'])?><?php endif; ?></td>
<td><?=e($row['description'])?><?php if ($row['reversal_reason']): ?><small class="d-block text-muted">Reversal: <?=e($row['reversal_reason'])?></small><?php endif; ?></td>
<td class="text-nowrap"><?= $row['type'] === 'transfer' ? '' : ((float)$row['account_delta'] < 0 ? '−' : '+') ?><?=money($row['amount'])?></td>
<td><span class="status-badge status-badge--<?=$row['status']==='posted'?'success':'neutral'?>"><?=e(ucfirst($row['status']))?></span></td>
<td>
<?php if ($row['source'] === 'expense' && can('expenses.view')): ?><a class="action-button action-button--primary btn btn-sm btn-outline-secondary" href="<?=e(url('expenses/'.$row['id']))?>"><i data-feather="eye" aria-hidden="true"></i>View expense</a>
<?php elseif ($row['source'] === 'transaction'): ?>
<a class="action-button action-button--primary btn btn-sm btn-outline-secondary" href="<?=e(url('transactions/'.$row['id']))?>"><i data-feather="eye" aria-hidden="true"></i>View</a><?php endif; ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php $total=$data['total']; $page=$data['page']; $per=$data['per']; require dirname(__DIR__).'/partials/pagination.php'; ?>
</div></div>