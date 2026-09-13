<?php
use App\Services\Authorization;
$module = 'categories';
$heading = 'Categories';
$subtitle = can('categories.view_all') ? 'Manage global and user-owned categories.' : 'Use global categories or create your own personal categories.';
$actionUrl = can('categories.create') ? 'categories/create' : null;
$actionLabel = '+ Add Category';
$showOwner = can('categories.view_all');
require dirname(__DIR__).'/partials/page-header.php';
?>
<div class="card"><div class="card-body">
<?php if ($showOwner): ?>
<form method="get" action="<?=e(url('categories'))?>" class="row g-3 align-items-end mb-4">
<div class="col-md-3"><label for="scope" class="form-label">Scope</label><select id="scope" name="scope" class="form-select"><option value="">All Categories</option><option value="global" <?=selected($filters['scope'],'global')?>>Global Categories</option><option value="personal" <?=selected($filters['scope'],'personal')?>>Personal Categories</option></select></div>
<div class="col-md-4"><label for="owner_id" class="form-label">Owner</label><select id="owner_id" name="owner_id" class="form-select"><option value="">All users</option><?php foreach ($owners as $owner): ?><option value="<?=e($owner['id'])?>" <?=selected($filters['owner_id'],$owner['id'])?>><?=e($owner['name'].' ('.$owner['email'].')')?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><label for="filter-status" class="form-label">Status</label><select id="filter-status" name="status" class="form-select"><option value="">All statuses</option><option value="active" <?=selected($filters['status'],'active')?>>Active</option><option value="inactive" <?=selected($filters['status'],'inactive')?>>Inactive</option></select></div>
<div class="col-md-3 d-flex gap-2"><button class="action-button action-button--primary btn btn-primary" type="submit"><i data-feather="filter" aria-hidden="true"></i>Filter</button><a class="action-button action-button--neutral btn btn-outline-secondary" href="<?=e(url('categories'))?>"><i data-feather="rotate-ccw" aria-hidden="true"></i>Clear</a></div>
</form>
<?php endif; ?>
<div class="table-responsive" role="region" aria-label="Categories table" tabindex="0"><table class="table crud-table mb-0">
<thead><tr><th scope="col">Badge / Icon</th><th scope="col">Category</th><th scope="col">Scope</th><?php if ($showOwner): ?><th scope="col">Owner</th><?php endif; ?><th scope="col">Description</th><th scope="col">Status</th><th scope="col" class="text-end">Actions</th></tr></thead>
<tbody>
<?php if (!$rows): ?><tr><td colspan="<?=$showOwner ? 7 : 6?>" class="text-center py-5"><p class="text-muted">No categories found.</p><?php if ($actionUrl): ?><a class="action-button action-button--success btn btn-outline-primary" href="<?=e(url($actionUrl))?>"><i data-feather="plus-circle" aria-hidden="true"></i><?=e(ltrim($actionLabel, "+ "))?></a><?php endif; ?></td></tr><?php endif; ?>
<?php foreach ($rows as $r): ?>
<tr>
<td><span class="category-badge" style="background-color:<?=e(preg_match('/^#[a-fA-F0-9]{6}$/D', $r['display_color'] ?? '') ? $r['display_color'] : '#3b7ddd')?>"><?php if (!empty($r['badge'])): ?><?=e($r['badge'])?><?php else: ?><i data-feather="<?=e($r['display_icon'] ?? 'tag')?>"></i><?php endif; ?></span></td>
<td class="fw-semibold"><?=e($r['name'])?></td>
<td><span class="status-badge status-badge--<?=$r['owner_id'] === null ? 'primary' : 'neutral'?>"><i data-feather="<?=$r['owner_id'] === null ? 'globe' : 'user'?>" aria-hidden="true"></i><?=$r['owner_id'] === null ? 'Global' : 'Personal'?></span></td>
<?php if ($showOwner): ?><td><span class="inline-icon-text"><i data-feather="<?=$r['owner_id'] === null ? 'users' : 'user'?>" aria-hidden="true"></i><?=e($r['owner_name'] ?? 'Everyone')?></span></td><?php endif; ?>
<td class="table-description"><?=e($r['description'] ?? '')?></td>
<td><span class="status-badge status-badge--<?=in_array($r['status'], ['active', 'posted'], true) ? 'success' : 'neutral'?>"><i data-feather="<?=in_array($r['status'], ['active', 'posted'], true) ? 'check-circle' : 'pause-circle'?>" aria-hidden="true"></i><?=e(ucfirst($r['status']))?></span></td>
<td><?php
$rowId = $r['id']; $rowName = $r['name'];
$manageable = Authorization::categoryManageable(auth_user(), $r);
$mayEdit = $manageable && can('categories.edit');
$mayDelete = $manageable && can('categories.delete');
$mayAppearance = can('categories.customize') && Authorization::categoryCustomizable(auth_user(), $r);
require dirname(__DIR__).'/partials/row-actions.php';
?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
