<?php
$module = 'categories';
$heading = 'Categories';
$subtitle = is_adminish() ? 'Manage system expense categories.' : 'Customize category badges for your own tracking.';
$actionUrl = is_adminish() && can('categories.create') ? 'categories/create' : null;
$actionLabel = '+ Add Category';
require dirname(__DIR__).'/partials/page-header.php';
?>

<div class="card"><div class="card-body">
<div class="table-responsive" role="region" aria-label="Categories table" tabindex="0"><table class="table crud-table mb-0">
<thead><tr><th scope="col">Badge / Icon</th><th scope="col">Category</th><th scope="col">Description</th><th scope="col">Status</th><th scope="col" class="text-end">Actions</th></tr></thead>
<tbody>
<?php if (!$rows): ?><tr><td colspan="5" class="text-center py-5"><p class="text-muted">No categories found.</p><?php if ($actionUrl): ?><a class="btn btn-outline-primary" href="<?=e(url($actionUrl))?>"><?=e($actionLabel)?></a><?php endif; ?></td></tr><?php endif; ?>
<?php foreach ($rows as $r): ?>
<tr>
<td><span class="category-badge" style="background-color:<?=e(preg_match('/^#[a-fA-F0-9]{6}$/D', $r['display_color'] ?? '') ? $r['display_color'] : '#3b7ddd')?>"><?php if (!empty($r['badge'])): ?><?=e($r['badge'])?><?php else: ?><i data-feather="<?=e($r['display_icon'] ?? 'tag')?>"></i><?php endif; ?></span></td>
<td class="fw-semibold"><?=e($r['name'])?></td><td class="table-description"><?=e($r['description'] ?? '')?></td>
<td><span class="badge bg-<?=$r['status'] === 'active' ? 'success' : 'secondary'?>"><?=e(ucfirst($r['status']))?></span></td>
<td><?php $rowId = $r['id']; $rowName = $r['name']; $mayEdit = is_adminish() && can('categories.edit'); $mayDelete = is_adminish() && can('categories.delete'); require dirname(__DIR__).'/partials/row-actions.php'; ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div></div>