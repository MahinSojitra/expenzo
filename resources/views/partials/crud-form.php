<div class="crud-form">
    <?php $actionUrl = null; $backUrl = $module; $backLabel = 'Back to '.ucfirst($module); $confirmEntity = ['categories' => 'Category', 'accounts' => 'Account', 'budgets' => 'Budget', 'expenses' => 'Expense', 'users' => 'User', 'roles' => 'Role'][$module] ?? 'Record'; require __DIR__.'/page-header.php'; ?>
    <div class="card"><div class="card-body">
        <form method="post" action="<?=e(url($formAction))?>" <?php if (($editing ?? false) && isset($fields['status'])): ?>data-confirm-status-field="status" data-confirm-status-value="inactive" data-confirm-title="Deactivate <?=e($confirmEntity)?>" data-confirm-subtitle="This will restrict future use." data-confirm-message="<?php if (($module ?? '') === 'users'): ?>Deactivating this user prevents them from signing in and blocks their next request. Their existing records and audit history stay saved.<?php elseif (($module ?? '') === 'accounts'): ?>Deactivating this account keeps existing expenses and balances, but it should no longer be used for new active expense entries.<?php elseif (($module ?? '') === 'categories'): ?>Deactivating this category keeps past expenses and reports, but it should no longer be used for new active expense entries.<?php elseif (($module ?? '') === 'budgets'): ?>Deactivating this budget keeps its history, but it will no longer be treated as an active budget limit.<?php else: ?>Deactivating this record keeps its history but restricts future use.<?php endif; ?>" data-confirm-button="Deactivate"<?php endif; ?>>
            <?=csrf_field()?>
            <div class="row"><?php require __DIR__.'/form-fields.php'; ?></div>
            <?php if (!empty($formNote)): ?><p class="text-muted"><?=e($formNote)?></p><?php endif; ?>
            <div class="form-actions">
                <a class="action-button action-button--neutral btn btn-outline-secondary" href="<?=e(url($module))?>"><i data-feather="x" aria-hidden="true"></i>Cancel</a>
                <button type="submit" class="action-button action-button--success btn btn-primary"><i data-feather="save" aria-hidden="true"></i><?=e($submitLabel)?></button>
            </div>
        </form>
    </div></div>
</div>
