<?php
$module = 'budgets';
$heading = $editing ? 'Edit Budget' : 'Create Budget';
$subtitle = 'Enter your budget details below.';
$formAction = $editing ? $module.'/'.$record['id'].'/update' : $module;
$submitLabel = $editing ? 'Update Budget' : 'Create Budget';
$statusField = ['label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'default' => 'active', 'required' => true];
$categoryOptions = ['' => 'All Categories'];
foreach ($categories as $category) $categoryOptions[$category['id']] = $category['name'] . ($category['status'] === 'inactive' ? ' (inactive)' : '');
$fields = [
    'category_id' => ['label' => 'Category', 'type' => 'select', 'options' => $categoryOptions, 'wide' => true],
    'start_date' => ['label' => 'Start date', 'type' => 'date', 'required' => true, 'default' => date('Y-m-01')],
    'end_date' => ['label' => 'End date', 'type' => 'date', 'required' => true, 'default' => date('Y-m-t')],
    'budget_amount' => ['label' => 'Budget amount', 'type' => 'number', 'min' => '0.01', 'step' => '0.01', 'required' => true],
    'warning_threshold' => ['label' => 'Warning threshold (%)', 'type' => 'number', 'min' => 1, 'max' => 100, 'step' => '0.01', 'default' => 80, 'required' => true],
    'status' => $statusField,
];
require dirname(__DIR__).'/partials/crud-form.php';