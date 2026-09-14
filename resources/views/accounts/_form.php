<?php
$module = 'accounts';
$heading = $editing ? 'Edit Account' : 'Add Account';
$subtitle = 'Enter your account details below.';
$formAction = $editing ? $module.'/'.$record['id'].'/update' : $module;
$submitLabel = $editing ? 'Update Account' : 'Save Account';
$statusField = ['label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'default' => 'active', 'required' => true];
$types = [
    'Cash' => ['label' => 'Cash', 'icon' => 'dollar-sign'],
    'Bank' => ['label' => 'Bank', 'icon' => 'briefcase'],
    'Card' => ['label' => 'Card', 'icon' => 'credit-card'],
    'Credit Card' => ['label' => 'Credit Card', 'icon' => 'credit-card'],
    'Debit Card' => ['label' => 'Debit Card', 'icon' => 'credit-card'],
    'UPI' => ['label' => 'UPI', 'icon' => 'smartphone'],
    'Wallet' => ['label' => 'Wallet', 'icon' => 'pocket'],
];
$fields = [
    'name' => ['label' => 'Account name', 'required' => true, 'maxlength' => 120],
    'type' => ['label' => 'Account type', 'type' => 'select', 'options' => $types, 'required' => true],
];
if (!$editing) $fields['opening_balance'] = ['label' => 'Opening balance', 'type' => 'number', 'min' => '0', 'max' => '9999999999999.99', 'step' => '0.01', 'help' => 'Enter zero or a positive balance. Negative opening balances are not allowed.', 'default' => '0', 'required' => true];
$fields['status'] = $statusField;
$fields['description'] = ['label' => 'Description', 'type' => 'textarea', 'wide' => true];
$formNote = $editing ? 'Opening balance: ' . money($record['opening_balance']) . '. Opening and current balances are preserved when you edit account details.' : 'This account will belong to you.';
require dirname(__DIR__).'/partials/crud-form.php';
