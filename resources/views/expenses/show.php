<?php
$owned = (int)$expense['user_id'] === (int)auth_user()['id'];
$status = (string)$expense['status'];
$statusIcon = match ($status) { 'posted' => 'check-circle', 'void' => 'x-circle', default => 'pause-circle' };
$statusColor = match ($status) { 'posted' => 'success', 'void' => 'danger', default => 'neutral' };
?>
<div class="expense-details">
    <div class="page-header">
        <div><h1 class="h3 mb-1">Expense #<?=e($expense['id'])?></h1><p class="text-muted mb-0">View payment details, notes and your receipt.</p></div>
        <div class="expense-detail-actions">
            <a href="<?=e(url('expenses'))?>" class="action-button action-button--neutral btn btn-outline-secondary"><i data-feather="arrow-left" aria-hidden="true"></i>Back to Expense</a>
            <?php if (can('expenses.edit') && $owned): ?><a href="<?=e(url('expenses/'.$expense['id'].'/edit'))?>" class="action-button action-button--primary btn btn-primary"><i data-feather="edit-3" aria-hidden="true"></i>Edit Expense</a><?php endif; ?>
        </div>
    </div>

    <div class="card"><div class="card-body">
        <div class="expense-overview">
            <div class="expense-amount">
                <span class="expense-detail-icon" aria-hidden="true"><?=currency_icon()?></span>
                <div><span class="expense-detail-label">Total amount</span><div class="expense-amount-value"><?=money($expense['amount'])?></div></div>
            </div>
            <div class="expense-overview-item"><span class="expense-detail-label"><i data-feather="calendar" aria-hidden="true"></i>Expense date</span><strong><?=e(display_date($expense['expense_date']))?></strong></div>
            <div class="expense-overview-item"><span class="expense-detail-label"><i data-feather="activity" aria-hidden="true"></i>Status</span><span class="status-badge status-badge--<?=$statusColor?>"><i data-feather="<?=$statusIcon?>" aria-hidden="true"></i><?=e(ucfirst($status))?></span></div>
        </div>

        <div class="expense-detail-grid">
            <section class="expense-detail-section" aria-labelledby="expense-payment-heading">
                <h2 id="expense-payment-heading"><i data-feather="credit-card" aria-hidden="true"></i>Payment details</h2>
                <dl class="expense-payment-fields">
                    <div><dt>Category</dt><dd><?=category_label($expense['category_name'] ?? 'Uncategorized', $expense['category_icon'] ?? null, $expense['category_color'] ?? null, $expense['badge'] ?? null)?></dd></div>
                    <div><dt>Account</dt><dd><?=account_type_label($expense['account_type'] ?? '', $expense['account_name'] ?? 'Unavailable')?></dd></div>
                    <?php if (can('finance.view_all')): ?><div><dt>Recorded by</dt><dd class="expense-person"><i data-feather="user" aria-hidden="true"></i><?=e($expense['user_name'] ?? 'Unavailable')?></dd></div><?php endif; ?>
                </dl>
            </section>
            <section class="expense-detail-section" aria-labelledby="expense-description-heading">
                <h2 id="expense-description-heading"><i data-feather="align-left" aria-hidden="true"></i>Description</h2>
                <p class="expense-detail-copy"><?=nl2br(e($expense['description']))?></p>
            </section>
            <section class="expense-detail-section" aria-labelledby="expense-notes-heading">
                <h2 id="expense-notes-heading"><i data-feather="file-text" aria-hidden="true"></i>Notes</h2>
                <?php if (trim((string)($expense['notes'] ?? '')) !== ''): ?><p class="expense-detail-copy"><?=nl2br(e($expense['notes']))?></p>
                <?php else: ?><p class="expense-detail-empty">No notes added.</p><?php endif; ?>
            </section>
            <section class="expense-detail-section" aria-labelledby="expense-receipt-heading">
                <h2 id="expense-receipt-heading"><i data-feather="paperclip" aria-hidden="true"></i>Receipt</h2>
                <?php if (!empty($expense['receipt_path'])): ?>
                <div class="expense-receipt">
                    <span class="expense-detail-icon"><i data-feather="file" aria-hidden="true"></i></span>
                    <div class="expense-receipt-info"><strong>Expense receipt</strong><span><?=e(strtoupper(pathinfo($expense['receipt_path'], PATHINFO_EXTENSION)))?> attachment</span></div>
                    <a href="<?=e(url('expenses/'.$expense['id'].'/receipt'))?>" target="_blank" rel="noopener" class="action-button action-button--primary btn btn-sm"><i data-feather="external-link" aria-hidden="true"></i>View Receipt<span class="visually-hidden"> (opens in a new tab)</span></a>
                </div>
                <?php else: ?><p class="expense-detail-empty">No receipt attached.</p><?php endif; ?>
            </section>
        </div>
        <?php if (can('expenses.delete') && $owned): ?>
        <div class="expense-detail-footer">
            <form method="post" action="<?=e(url('expenses/'.$expense['id'].'/delete'))?>" data-confirm-title="Delete Expense" data-confirm-subtitle="This action cannot be undone." data-confirm-message="Deleting this expense permanently removes it and restores the amount to the linked account balance." data-confirm-button="Delete Expense">
                <?=csrf_field()?><button type="submit" class="action-button action-button--danger btn btn-outline-danger"><i data-feather="trash-2" aria-hidden="true"></i>Delete Expense</button>
            </form>
        </div>
        <?php endif; ?>
    </div></div>
</div>
