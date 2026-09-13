<?php
$module = 'accounts';
$heading = 'Accounts';
$subtitle = is_adminish() ? 'View user-owned payment sources.' : 'Manage where your expenses are paid from.';
$actionUrl = can('accounts.create') ? 'accounts/create' : null;
$actionLabel = '+ Add Account';
require dirname(__DIR__).'/partials/page-header.php';
?>

<div class="card"><div class="card-body">
<div class="table-responsive" role="region" aria-label="Accounts table" tabindex="0"><table class="table crud-table mb-0">
<thead><tr><?php if (is_adminish()): ?><th scope="col">User</th><?php endif; ?><th scope="col">Account Name</th><th scope="col">Type</th><th scope="col">Opening Balance</th><th scope="col">Current Balance</th><th scope="col">Status</th><th scope="col" class="text-end">Actions</th></tr></thead>
<tbody>
<?php if (!$rows): ?><tr><td colspan="<?=is_adminish() ? 7 : 6?>" class="text-center py-5"><p class="text-muted">No accounts found.</p><?php if ($actionUrl): ?><a class="btn btn-outline-primary" href="<?=e(url($actionUrl))?>"><?=e($actionLabel)?></a><?php endif; ?></td></tr><?php endif; ?>
<?php foreach ($rows as $r): ?>
<tr>
<?php if (is_adminish()): ?><td><?=e($r['user_name'] ?? '')?></td><?php endif; ?>
<td><div class="fw-semibold"><?=e($r['name'])?></div><small class="text-muted table-description"><?=e($r['description'] ?? '')?></small></td>
<td><?=e($r['type'])?></td><td class="text-nowrap"><?=money($r['opening_balance'])?></td><td class="fw-semibold text-nowrap"><?=money($r['balance'])?></td>
<td><span class="badge bg-<?=$r['status'] === 'active' ? 'success' : 'secondary'?>"><?=e(ucfirst($r['status']))?></span></td>
<td><?php $rowId = $r['id']; $rowName = $r['name']; $owned = (int)$r['user_id'] === (int)\App\Core\Session::get('user_id'); $mayEdit = $owned && can('accounts.edit'); $mayDelete = $owned && can('accounts.delete'); require dirname(__DIR__).'/partials/row-actions.php'; ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div></div>