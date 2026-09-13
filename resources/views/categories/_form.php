<?php
$module = 'categories';
$heading = $editing ? 'Edit Category' : 'Add Category';
$subtitle = 'Manage the system category details.';
$formAction = $editing ? $module.'/'.$record['id'].'/update' : $module;
$submitLabel = $editing ? 'Update Category' : 'Save Category';
$statusField = ['label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'default' => 'active', 'required' => true];
$fields = [
    'name' => ['label' => 'Name', 'required' => true, 'maxlength' => 120, 'wide' => true],
    'description' => ['label' => 'Description', 'type' => 'textarea', 'wide' => true],
    'icon' => ['label' => 'Default icon', 'default' => 'tag', 'required' => true, 'maxlength' => 80, 'help' => 'Use a Feather icon name, such as tag, coffee, or shopping-bag.'],
    'color' => ['label' => 'Default color', 'type' => 'color', 'default' => '#3b7ddd', 'required' => true],
    'status' => $statusField,
];
require dirname(__DIR__).'/partials/crud-form.php';