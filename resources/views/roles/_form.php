<?php
$heading = $editing ? 'Edit Role' : 'Add Role';
$subtitle = 'Choose which pages and actions this role can access.';
$actionUrl = null;
$system = !empty($record['is_system']);
$moduleLabels = ['dashboard'=>'Dashboard','expenses'=>'Expenses','categories'=>'Categories','accounts'=>'Accounts','budgets'=>'Budgets','reports'=>'Reports','users'=>'Users','roles'=>'Roles','permissions'=>'Permission Administration','settings'=>'Settings','audit'=>'Audit Logs','finance'=>'Finance Visibility'];
?>
<div class="role-form">
<a class="d-inline-block mb-3" href="<?=e(url('roles'))?>">&larr; Back to Roles</a>
<?php require dirname(__DIR__).'/partials/page-header.php'; ?>
<form method="post" action="<?=e(url($editing ? 'roles/'.$record['id'].'/update' : 'roles'))?>" data-permissions-form>
<?=csrf_field()?>
<?php if ($errors): ?><div class="alert alert-danger" role="alert">Please correct the highlighted fields.<?php if (!empty($errors['permissions'])): ?><div><?=e($errors['permissions'])?></div><?php endif; ?></div><?php endif; ?>
<div class="card"><div class="card-body">
<?php if ($system): ?><p class="alert alert-info">This is a system role. Its name and active status are protected.</p><?php endif; ?>
<div class="row">
<?php
$fields = [
    'name' => ['label'=>'Role name','required'=>true,'maxlength'=>80],
    'status' => ['label'=>'Status','type'=>'select','options'=>['active'=>'Active','inactive'=>'Inactive'],'default'=>'active','required'=>true],
    'description' => ['label'=>'Description','type'=>'textarea','wide'=>true],
];
require dirname(__DIR__).'/partials/form-fields.php';
?>
</div>
<?php if ($system): ?><p class="text-muted mb-0">Only Super Admin can update the permissions of this system role.</p><?php else: ?><p class="text-muted mb-0">Deactivating a role blocks its users on their next request. Reassign those users before deleting it.</p><?php endif; ?>
</div></div>
<div class="page-header"><div><h2 class="h4 mb-1">Permissions</h2><p class="text-muted mb-0">Access is limited to the selected actions. Ownership restrictions still apply.</p></div>
<div class="permission-tools"><button type="button" class="btn btn-sm btn-outline-primary" data-permission-action="all">Select all permissions</button><button type="button" class="btn btn-sm btn-outline-secondary" data-permission-action="none">Clear all</button></div></div>
<div class="row">
<?php foreach ($groups as $group => $permissions): ?>
<div class="col-md-6 col-xl-4 mb-3"><fieldset class="card h-100 permission-group" data-permission-group>
<div class="card-body">
<legend class="h5"><?=e($moduleLabels[$group] ?? ucwords(str_replace('_',' ',$group)))?></legend>
<div class="permission-tools mb-3"><button type="button" class="btn btn-sm btn-outline-primary" data-permission-action="group-all">Select all</button><button type="button" class="btn btn-sm btn-outline-secondary" data-permission-action="group-none">Clear all</button></div>
<?php foreach ($permissions as $permission):
$action = substr($permission['name'], strpos($permission['name'], '.') + 1);
$label = ucwords(str_replace(['.','_'], ' ', $action));
?>
<div class="form-check mb-3">
<input class="form-check-input" type="checkbox" name="permissions[]" id="permission-<?=e($permission['id'])?>" value="<?=e($permission['id'])?>" <?=checked(in_array((int)$permission['id'], $selectedPermissions, true))?> <?=$permission['grantable'] ? '' : 'disabled'?>>
<label class="form-check-label" for="permission-<?=e($permission['id'])?>"><?=e($label)?><small class="d-block text-muted"><?=e($permission['name'])?><?=$permission['grantable'] ? '' : ' · Not assignable by you'?></small></label>
</div>
<?php endforeach; ?>
</div></fieldset></div>
<?php endforeach; ?>
</div>
<div class="card"><div class="card-body"><div class="form-actions mt-0 border-0 pt-0">
<a class="btn btn-outline-secondary" href="<?=e(url('roles'))?>">Cancel</a><button class="btn btn-primary" type="submit"><?=$editing ? 'Update Role' : 'Save Role'?></button>
</div></div></div>
</form>
</div>