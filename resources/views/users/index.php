<?php
$module = 'users';
$heading = 'Users';
$subtitle = 'Manage user profiles and access.';
$actionUrl = (can('users.create') && can('users.assign_role')) ? 'users/create' : null;
$actionLabel = '+ Add User';
require dirname(__DIR__).'/partials/page-header.php';
?>

<div class="card"><div class="card-body">
<form method="get" action="<?=e(url('users'))?>" class="list-search mb-4" role="search">
    <div class="flex-grow-1"><label for="user-search" class="form-label">Search users</label><div class="input-group search-input-group"><span class="input-group-text"><i data-feather="search" aria-hidden="true"></i></span><input id="user-search" class="form-control" name="q" value="<?=e($_GET['q'] ?? '')?>" placeholder="Name or email"></div></div>
    <button class="action-button action-button--primary btn btn-primary" type="submit"><i data-feather="search" aria-hidden="true"></i>Search</button>
    <?php if (!empty($_GET['q'])): ?><a class="action-button action-button--neutral btn btn-outline-secondary" href="<?=e(url('users'))?>"><i data-feather="rotate-ccw" aria-hidden="true"></i>Clear</a><?php endif; ?>
</form>
<div class="table-responsive" role="region" aria-label="Users table" tabindex="0"><table class="table crud-table mb-0">
<thead><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col">Created At</th><th scope="col" class="text-end">Actions</th></tr></thead>
<tbody>
<?php $rows = $data['rows']; ?>
<?php if (!$rows): ?><tr><td colspan="6" class="text-center py-5"><p class="text-muted">No users found.</p><?php if ($actionUrl): ?><a class="action-button action-button--success btn btn-outline-primary" href="<?=e(url($actionUrl))?>"><i data-feather="plus-circle" aria-hidden="true"></i><?=e(ltrim($actionLabel, "+ "))?></a><?php endif; ?></td></tr><?php endif; ?>
<?php foreach ($rows as $u): ?>
<tr><td class="fw-semibold"><?=e($u['name'])?></td><td><?=e($u['email'])?></td><td><?=e($u['role_names'])?></td><td><span class="status-badge status-badge--<?=in_array($u['status'], ['active', 'posted'], true) ? 'success' : 'neutral'?>"><i data-feather="<?=in_array($u['status'], ['active', 'posted'], true) ? 'check-circle' : 'pause-circle'?>" aria-hidden="true"></i><?=e(ucfirst($u['status']))?></span></td><td class="text-nowrap"><?=e(display_date($u['created_at']))?></td>
<td><?php $rowId = $u['id']; $rowName = $u['name']; $mayEdit = can('users.edit') && ($u['may_edit'] ?? false); $mayDelete = false; require dirname(__DIR__).'/partials/row-actions.php'; ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php $total = $data['total']; $page = $data['page']; $per = $data['per']; require dirname(__DIR__).'/partials/pagination.php'; ?>
</div></div>