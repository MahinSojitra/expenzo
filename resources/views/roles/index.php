<?php
use App\Services\Authorization;
$heading = 'Roles & Permissions';
$subtitle = 'Manage roles and the access assigned to their users.';
$actionUrl = (can('roles.create') && can('permissions.manage')) ? 'roles/create' : null;
$actionLabel = '+ Add Role';
require dirname(__DIR__).'/partials/page-header.php';
?>
<div class="card"><div class="card-body"><div class="table-responsive" role="region" aria-label="Roles table" tabindex="0">
<table class="table crud-table mb-0">
<thead><tr><th>Role Name</th><th>Description</th><th>Status</th><th>Users</th><th class="text-end">Actions</th></tr></thead>
<tbody>
<?php if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-5">No roles found.</td></tr><?php endif; ?>
<?php foreach ($rows as $role): $manageable = Authorization::canManageRole(auth_user(), $role); ?>
<tr><td><strong><?=e($role['name'])?></strong><?php if ($role['is_system']): ?><span class="badge bg-secondary ms-2">System</span><?php endif; ?></td>
<td class="table-description"><?=e($role['description'] ?? '')?></td>
<td><span class="badge bg-<?=$role['status'] === 'active' ? 'success' : 'secondary'?>"><?=e(ucfirst($role['status']))?></span></td>
<td><?=e($role['user_count'])?></td><td><div class="table-actions">
<?php if (can('roles.edit') && can('permissions.manage') && $manageable): ?><a class="btn btn-sm btn-outline-primary" href="<?=e(url('roles/'.$role['id'].'/edit'))?>">Edit<span class="visually-hidden"> <?=e($role['name'])?></span></a><?php endif; ?>
<?php if (can('roles.delete') && $manageable && !$role['is_system'] && !(int)$role['user_count']): ?>
<form method="post" action="<?=e(url('roles/'.$role['id'].'/delete'))?>" onsubmit="return confirm('Delete this role? This cannot be undone.');"><?=csrf_field()?><button class="btn btn-sm btn-outline-danger">Delete<span class="visually-hidden"> <?=e($role['name'])?></span></button></form>
<?php elseif ($role['is_system']): ?><span class="text-muted small">Protected</span>
<?php elseif ((int)$role['user_count']): ?><span class="text-muted small">Assigned</span><?php endif; ?>
</div></td></tr>
<?php endforeach; ?>
</tbody></table>
</div></div></div>