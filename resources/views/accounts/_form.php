<?php
$module = 'accounts';
$heading = $editing ? 'Edit Account' : 'Add Account';
$subtitle = 'Enter your account details below.';
$formAction = $editing ? $module.'/'.$record['id'].'/update' : $module;
$submitLabel = $editing ? 'Update Account' : 'Save Account';
$statusField = ['label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'default' => 'active', 'required' => true];
$types = ['Cash', 'Bank', 'Card', 'Credit Card', 'Debit Card', 'UPI', 'Wallet'];
$fields = [
    'name' => ['label' => 'Account name', 'required' => true, 'maxlength' => 120],
    'type' => ['label' => 'Account type', 'type' => 'select', 'options' => array_combine($types, $types), 'required' => true],
];
if (!$editing) $fields['opening_balance'] = ['label' => 'Opening balance', 'type' => 'number', 'step' => '0.01', 'default' => '0', 'required' => true];
$fields['status'] = $statusField;
$fields['description'] = ['label' => 'Description', 'type' => 'textarea', 'wide' => true];
$formNote = $editing ? 'Opening balance: ' . money($record['opening_balance']) . '. Opening and current balances are preserved when you edit account details.' : 'This account will belong to you.';
require dirname(__DIR__).'/partials/crud-form.php';