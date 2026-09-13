<?php
$module = 'categories';
$heading = $editing ? 'Edit Category' : 'Add Category';
$global = $editing ? $record['owner_id'] === null : can('categories.manage_global');
$subtitle = $global ? 'Global category, available to everyone.' : 'Personal category, available only to its owner.';
$formNote = $editing && !empty($record['owner_name']) ? 'Owner: '.$record['owner_name'].'. Ownership and scope cannot be changed.' : ($global ? 'This category will be available to all users.' : 'This category belongs to you.');
$formAction = $editing ? $module.'/'.$record['id'].'/update' : $module;
$submitLabel = $editing ? 'Update Category' : 'Save Category';
$statusField = ['label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'default' => 'active', 'required' => true];
$fields = [
    'name' => ['label' => 'Name', 'required' => true, 'maxlength' => 120, 'wide' => true],
    'description' => ['label' => 'Description', 'type' => 'textarea', 'wide' => true],
    'icon' => ['type' => 'icon', 'label' => 'Default icon', 'default' => 'tag', 'required' => true, 'maxlength' => 80, 'help' => 'Search by name and choose an icon.'],
    'color' => ['label' => 'Default color', 'type' => 'color', 'default' => '#3b7ddd', 'required' => true],
    'status' => $statusField,
];
require dirname(__DIR__).'/partials/crud-form.php';