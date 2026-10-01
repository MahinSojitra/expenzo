<?php
$t = $transaction;
$reversed = $t['reversed_at'] !== null;
$typeIcon = ['income'=>'trending-up','transfer'=>'repeat','adjustment'=>'sliders'][$t['type']] ?? 'list';
?>
<div class="expense-details transaction-details">
<div class="page-header">
<div><h1 class="h3 mb-1">Transaction #<?=e($t['id'])?></h1><p class="text-muted mb-0">View account movements and transaction history.</p></div>
<div class="expense-detail-actions"><a href="<?=e(url('transactions'))?>" class="action-button action-button--neutral btn btn-outline-secondary"><i data-feather="arrow-left" aria-hidden="true"></i>Back to Transactions</a>
<?php if ($mayReverse): ?><a href="#reverse-transaction" class="action-button action-button--danger btn btn-outline-danger"><i data-feather="rotate-ccw" aria-hidden="true"></i>Reverse Transaction</a><?php endif; ?>
</div></div>
<div class="card"><div class="card-body">
<div class="expense-overview">
<div class="expense-amount"><span class="expense-detail-icon" aria-hidden="true"><?=currency_icon()?></span><div><span class="expense-detail-label">Total amount</span><div class="expense-amount-value"><?=money($t['amount'])?></div></div></div>
<div class="expense-overview-item"><span class="expense-detail-label"><i data-feather="calendar" aria-hidden="true"></i>Transaction date</span><strong><?=e(display_date($t['transaction_date']))?></strong></div>
<div class="expense-overview-item"><span class="expense-detail-label"><i data-feather="activity" aria-hidden="true"></i>Status</span><span class="status-badge status-badge--<?=$reversed?'neutral':'success'?>"><i data-feather="<?=$reversed?'rotate-ccw':'check-circle'?>" aria-hidden="true"></i><?=$reversed?'Reversed':'Posted'?></span></div>
</div>
<div class="expense-detail-grid">
<section class="expense-detail-section">
<h2><i data-feather="credit-card" aria-hidden="true"></i>Transaction details</h2>
<dl class="expense-payment-fields">
<div><dt>Type</dt><dd><span class="inline-icon-text"><i data-feather="<?=e($typeIcon)?>" aria-hidden="true"></i><?=e(ucfirst($t['type']))?></span></dd></div>
<div><dt>Account owner</dt><dd class="expense-person"><i data-feather="user" aria-hidden="true"></i><?=e($t['user_name'])?></dd></div>
<div><dt><?=$t['type']==='transfer'?'Source account':'Account'?></dt><dd><?=account_type_label($t['account_type'],$t['account_name'])?></dd></div>
<?php if ($t['destination_account_id']): ?><div><dt>Destination account</dt><dd><?=account_type_label($t['destination_type'],$t['destination_name'])?></dd></div><?php endif; ?>
<div><dt>Original account movement</dt><dd><?=((float)$t['account_delta']<0?'-':'+').money($t['amount'])?><?php if ($t['destination_account_id']): ?> / +<?=money($t['amount'])?> to destination<?php endif; ?></dd></div>
<div><dt>Created</dt><dd><?=e(display_date($t['created_at'],true))?></dd></div>
</dl></section>
<section class="expense-detail-section"><h2><i data-feather="align-left" aria-hidden="true"></i>Description / reason</h2><p class="expense-detail-copy"><?=nl2br(e($t['description']))?></p></section>
<section class="expense-detail-section"><h2><i data-feather="clock" aria-hidden="true"></i>Activity</h2>
<?php if (!$t['activity']): ?><p class="expense-detail-empty">No activity recorded.</p><?php endif; ?>
<?php if ($t['activity']): ?>
<ol class="transaction-timeline" aria-label="Transaction activity">
<?php foreach ($t['activity'] as $activity): $isReversal = $activity['action'] === 'reverse'; ?>
<li class="transaction-timeline-item <?=$isReversal?'transaction-timeline-item--reversed':''?>">
<span class="transaction-timeline-dot" aria-hidden="true"></span>
<div class="transaction-timeline-title"><i data-feather="<?=$isReversal?'rotate-ccw':'plus-circle'?>" aria-hidden="true"></i><strong><?=$isReversal?'Transaction reversed':'Transaction created'?></strong></div>
<p>By <?=e($activity['actor_name'] ?? 'Unavailable')?></p>
<time datetime="<?=e(str_replace(' ', 'T', $activity['created_at']))?>"><?=e(display_date($activity['created_at'],true))?></time>
</li>
<?php endforeach; ?>
</ol>
<?php endif; ?>
</section>
<section class="expense-detail-section"><h2><i data-feather="rotate-ccw" aria-hidden="true"></i>Reversal</h2>
<div class="transaction-reversal-summary">
<div class="transaction-reversal-heading"><span class="transaction-reversal-icon"><i data-feather="<?=$reversed?'rotate-ccw':'check-circle'?>" aria-hidden="true"></i></span><div><strong><?=$reversed?'Reversal completed':'Transaction is active'?></strong><p><?=$reversed?'The original balance changes have been undone.':'The original balance changes are still applied.'?></p></div></div>
<?php if ($reversed): ?>
<dl class="transaction-reversal-meta"><div><dt>Reversed on</dt><dd><?=e(display_date($t['reversed_at'],true))?></dd></div>
<?php foreach ($t['activity'] as $activity): if ($activity['action'] !== 'reverse') continue; ?><div><dt>Reversed by</dt><dd><span class="inline-icon-text"><i data-feather="user" aria-hidden="true"></i><?=e($activity['actor_name'] ?? 'Unavailable')?></span></dd></div><?php endforeach; ?></dl>
<div class="transaction-reversal-reason"><span class="expense-detail-label">Reason for reversal</span><p><?=nl2br(e($t['reversal_reason']))?></p></div>
<?php else: ?><p class="transaction-reversal-note">Reversing undoes the account movement and keeps the original transaction in your history.</p><?php endif; ?>
</div>
</section>
</div>
<?php if ($errors && !$mayReverse): ?><div class="alert alert-danger" role="alert"><?=e($errors['reason'] ?? 'Unable to reverse this transaction.')?></div><?php endif; ?>
<?php if ($mayReverse): ?>
<div class="transaction-reversal-panel" id="reverse-transaction">
<div class="transaction-reversal-heading"><span class="transaction-reversal-icon"><i data-feather="rotate-ccw" aria-hidden="true"></i></span><div><h2>Reverse this transaction</h2><p>Undo the balance changes while keeping a complete record.</p></div></div>
<form method="post" action="<?=e(url('transactions/'.$t['id'].'/reverse'))?>" data-confirm-title="Reverse Transaction" data-confirm-subtitle="The original record will remain in history." data-confirm-message="This will undo the account balance changes. Reversal is blocked if it would overdraw an account." data-confirm-button="Reverse Transaction">
<?=csrf_field()?>
<label for="reversal-reason" class="form-label">Reason for reversal <span class="text-danger">*</span></label>
<textarea id="reversal-reason" name="reason" required maxlength="255" rows="3" placeholder="Explain why this transaction needs to be reversed" class="form-control <?=isset($errors['reason'])?'is-invalid':''?>" aria-describedby="reversal-reason-error reversal-help"><?=e(old('reason'))?></textarea>
<div id="reversal-reason-error" class="invalid-feedback <?=isset($errors['reason'])?'d-block':''?>" aria-live="polite"><?=e($errors['reason'] ?? '')?></div>
<p class="form-text" id="reversal-help">A reason is required. Reversal is allowed only once and cannot overdraw an account.</p><div class="transaction-reversal-actions">
<button type="submit" class="action-button action-button--danger btn btn-outline-danger"><i data-feather="rotate-ccw" aria-hidden="true"></i>Reverse Transaction</button>
</div></form></div>
<script>
document.addEventListener('DOMContentLoaded', () => {
 const reason = document.getElementById('reversal-reason');
 const feedback = document.getElementById('reversal-reason-error');
 const validate = () => {
  const message = !reason.value.trim() ? 'Enter a reason for reversal.' : ([...reason.value.trim()].length > 255 ? 'Use 255 characters or fewer.' : '');
  reason.setCustomValidity(message);
  reason.classList.toggle('is-invalid', Boolean(message));
  reason.setAttribute('aria-invalid', String(Boolean(message)));
  feedback.textContent = message;
  feedback.classList.toggle('d-block', Boolean(message));
 };
 reason.addEventListener('input', validate);
 reason.addEventListener('change', validate);
 reason.addEventListener('blur', validate);
 reason.form.addEventListener('submit', event => { validate(); if (!reason.form.checkValidity()) { event.preventDefault(); event.stopImmediatePropagation(); reason.reportValidity(); } });
});
</script>
<?php endif; ?>
</div></div></div>
