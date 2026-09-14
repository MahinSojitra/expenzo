<div class="table-actions">
    <?php $confirmEntity = ['categories' => 'Category', 'accounts' => 'Account', 'budgets' => 'Budget', 'expenses' => 'Expense', 'users' => 'User', 'roles' => 'Role'][$module] ?? 'Record'; ?>
    <?php if ($mayEdit): ?><a class="action-button action-button--primary btn btn-sm btn-outline-primary" href="<?=e(url($module.'/'.$rowId.'/edit'))?>"><i data-feather="edit-3" aria-hidden="true"></i>Edit<span class="visually-hidden"> <?=e($rowName)?></span></a><?php endif; ?>
    <?php if ($module === 'categories' && ($mayAppearance ?? false)): ?><a class="action-button action-button--purple btn btn-sm btn-outline-secondary" href="<?=e(url('categories/'.$rowId.'/appearance'))?>"><i data-feather="sliders" aria-hidden="true"></i>Appearance<span class="visually-hidden"> for <?=e($rowName)?></span></a><?php endif; ?>
    <?php if ($mayDelete): ?>
    <form method="post" action="<?=e(url($module.'/'.$rowId.'/delete'))?>" data-confirm-title="Delete <?=e($confirmEntity)?>" data-confirm-subtitle="This action cannot be undone." data-confirm-message="Deleting <?=e($rowName)?> will permanently remove this record. If it is already used by other records, the system may block deletion and you should deactivate it instead." data-confirm-button="Delete">
        <?=csrf_field()?><button type="submit" class="action-button action-button--danger btn btn-sm btn-outline-danger"><i data-feather="trash-2" aria-hidden="true"></i>Delete<span class="visually-hidden"> <?=e($rowName)?></span></button>
    </form>
    <?php endif; ?>
    <?php if (!$mayEdit && !$mayDelete && !($module === 'categories' && ($mayAppearance ?? false))): ?><span class="text-muted">&mdash;</span><?php endif; ?>
</div>
