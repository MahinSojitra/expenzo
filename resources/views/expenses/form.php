<?php
$errors = $errors ?? [];
$editing = ($mode ?? 'create') === 'edit';
$value = static fn(string $name, mixed $default = '') => old($name, $expense[$name] ?? $default);
$invalid = static fn(string $name): string => isset($errors[$name]) ? ' is-invalid' : '';
$feedback = static function(string $name) use ($errors): void {
    if (isset($errors[$name])) echo '<div id="'.e($name).'-error" class="invalid-feedback d-block">'.e($errors[$name]).'</div>';
};
?>
<?php if (!empty($error)): ?><div class="alert alert-danger" role="alert"><?=e($error)?></div><?php endif; ?>
<div class="page-header"><div><h1 class="h3 mb-1"><?=$editing ? 'Edit Expense' : 'Add Expense'?></h1><p class="text-muted mb-0"><?=$editing ? 'Update the details of this expense.' : 'Record a new expense.'?></p></div><a href="<?=e(url('expenses'))?>" class="action-button action-button--neutral btn btn-outline-secondary"><i data-feather="arrow-left" aria-hidden="true"></i>Back to Expenses</a></div>
<div class="card"><div class="card-body">
<form method="post" enctype="multipart/form-data" action="<?=e(url($editing ? 'expenses/'.$expense['id'] : 'expenses'))?>" data-expense-form data-currency="<?=e(currency_code())?>" data-original-account="<?=e($expense['account_id'] ?? '')?>" data-original-amount="<?=e($expense['amount'] ?? '0')?>" data-original-status="<?=e($expense['status'] ?? '')?>">
<?=csrf_field()?>
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="amount" class="form-label">Amount *</label>
        <div class="input-group amount-input-group"><span class="input-group-text currency-input-icon" aria-hidden="true"><?=currency_icon()?></span><input id="amount" type="number" name="amount" min="0.01" max="9999999999999.99" step="0.01" class="form-control<?=$invalid('amount')?>" required value="<?=e($value('amount'))?>" aria-describedby="amount-error"></div>
        <div id="amount-error" class="invalid-feedback<?=isset($errors['amount']) ? ' d-block' : ''?>" aria-live="polite"><?=e($errors['amount'] ?? '')?></div>
    </div>
    <div class="col-md-4 mb-3">
        <label for="expense_date" class="form-label">Expense Date *</label>
        <input id="expense_date" type="date" name="expense_date" class="form-control<?=$invalid('expense_date')?>" required value="<?=e($value('expense_date', date('Y-m-d')))?>">
        <?php $feedback('expense_date'); ?>
    </div>
    <div class="col-md-4 mb-3">
        <label for="expense-status" class="form-label">Status</label>
        <select id="expense-status" class="form-select<?=$invalid('status')?>" name="status" aria-describedby="status-help">
            <?php foreach (['posted' => 'Posted', 'draft' => 'Draft', 'void' => 'Void'] as $key => $label): if ($key === 'void' && !$editing) continue; ?>
            <option value="<?=$key?>" data-icon="<?=$key === 'posted' ? 'check-circle' : ($key === 'void' ? 'x-circle' : 'pause-circle')?>" <?=selected($value('status', 'posted'), $key)?>><?=$label?></option>
            <?php endforeach; ?>
        </select>
        <div id="status-help" class="form-text">Posted expenses deduct from the account. Drafts and void expenses do not.</div>
        <?php $feedback('status'); ?>
    </div>
    <div class="col-md-6 mb-3">
        <label for="expense-category" class="form-label">Category *</label>
        <select id="expense-category" class="form-select<?=$invalid('category_id')?>" name="category_id" required>
            <option value="" data-icon="tag">Select category</option>
            <?php foreach ($categories as $category): ?><option value="<?=$category['id']?>" data-icon="<?=e($category['display_icon'] ?? $category['icon'] ?? 'tag')?>" <?=selected($value('category_id'), $category['id'])?>><?=e($category['name'])?></option><?php endforeach; ?>
        </select>
        <?php $feedback('category_id'); ?>
    </div>
    <div class="col-md-6 mb-3">
        <label for="expense-account" class="form-label">Account *</label>
        <select id="expense-account" class="form-select<?=$invalid('account_id')?>" name="account_id" required data-show-disabled-options aria-describedby="account-balance-help account_id-error" <?=isset($errors['account_id']) ? 'aria-invalid="true"' : ''?>>
            <option value="" data-icon="credit-card">Select account</option>
            <?php foreach ($accounts as $account): ?><option value="<?=$account['id']?>" data-account-type="<?=e($account['type'] ?? '')?>" data-balance="<?=e($account['current_balance'])?>" <?=selected($value('account_id'), $account['id'])?>><?=e($account['name'])?> (<?=money($account['current_balance'])?>)</option><?php endforeach; ?>
        </select>
        <div id="account_id-error" class="invalid-feedback<?=isset($errors['account_id']) ? ' d-block' : ''?>" aria-live="polite"><?=e($errors['account_id'] ?? '')?></div>
        <div id="account-balance-help" class="form-text" aria-live="polite">Enter an amount to see which accounts have sufficient balance.</div>
    </div>
    <div class="col-12 mb-3">
        <label for="expense-description" class="form-label">Description *</label>
        <input id="expense-description" class="form-control<?=$invalid('description')?>" name="description" required maxlength="255" value="<?=e($value('description'))?>">
        <?php $feedback('description'); ?>
    </div>
    <div class="col-12 mb-3">
        <label for="expense-notes" class="form-label">Notes</label>
        <textarea id="expense-notes" class="form-control" name="notes" rows="4"><?=e($value('notes'))?></textarea>
    </div>
    <div class="col-md-6 mb-3">
        <label for="receipt" class="form-label">Receipt (JPG, PNG, PDF up to 5MB)</label>
        <input id="receipt" type="file" class="form-control" name="receipt" accept="image/jpeg,image/png,application/pdf">
        <?php if (!empty($expense['receipt_path'])): ?><div class="current-file mt-2"><i data-feather="paperclip" aria-hidden="true"></i><span>Current receipt</span><a href="<?=e(url('expenses/'.$expense['id'].'/receipt'))?>" target="_blank" rel="noopener">View file</a></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="form-text">If you selected a new receipt, please select it again before saving.</div><?php endif; ?>
    </div>
</div>
<button class="action-button action-button--success btn btn-primary" type="submit"><i data-feather="save" aria-hidden="true"></i>Save Expense</button>
</form>
</div></div>
