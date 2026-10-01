<?php
$module = 'transactions';
$heading = 'Add Transaction';
$subtitle = 'Record money received, move money between accounts, or correct a balance.';
$formAction = 'transactions';
$submitLabel = 'Post Transaction';
$types = [];
if (can('transactions.create')) $types += ['income'=>['label'=>'Income','icon'=>'trending-up'], 'transfer'=>['label'=>'Transfer','icon'=>'repeat']];
if (can('transactions.adjust')) $types['adjustment'] = ['label'=>'Balance adjustment','icon'=>'sliders'];
$options = [''=>['label'=>'Choose an account','icon'=>'credit-card']];
foreach ($accounts as $account) $options[$account['id']] = ['label'=>$account['name'].' - '.money($account['balance']), 'icon'=>account_type_icon($account['type'] ?? ''), 'subtitle'=>can('transactions.create_all') ? $account['user_name'] : ''];
$fields = [
    'type'=>['label'=>'Transaction type','type'=>'select','options'=>$types,'required'=>true],
    'transaction_date'=>['label'=>'Date','type'=>'date','default'=>date('Y-m-d'),'max'=>date('Y-m-d'),'required'=>true],
    'account_id'=>['label'=>'Account (source for transfers)','type'=>'select','options'=>$options,'required'=>true],
    'destination_account_id'=>['label'=>'Destination account (transfers only)','type'=>'select','options'=>$options],
    'amount'=>['label'=>'Amount','type'=>'number','min'=>'0.01','max'=>'9999999999999.99','step'=>'0.01','required'=>true],
    'direction'=>['label'=>'Adjustment direction','type'=>'select','options'=>['increase'=>['label'=>'Increase balance','icon'=>'plus-circle'],'decrease'=>['label'=>'Decrease balance','icon'=>'minus-circle']]],
    'description'=>['label'=>'Description / reason','maxlength'=>255,'required'=>true,'wide'=>true],
];
$formNote = 'Transactions post immediately. Transfers must use accounts belonging to the same user and do not count as income or expenses. For adjustments, explain the discrepancy after checking your bank statement. Posted entries can be reversed with permission.';
require dirname(__DIR__).'/partials/crud-form.php';
?>
<script type="application/json" id="transaction-validation-data"><?=json_encode(array_column($accounts, 'balance', 'id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?></script>
<script type="application/json" id="transaction-account-owners"><?=json_encode(array_column($accounts, 'user_id', 'id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?></script>
<script src="<?=e(asset('transaction-validation.js'))?>"></script>