<?php
$module = 'users';
$heading = $editing ? 'Edit User' : 'Add User';
$subtitle = 'Manage user details and access.';
$formAction = $editing ? $module.'/'.$record['id'].'/update' : $module;
$submitLabel = $editing ? 'Update User' : 'Save User';
$statusField = ['label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'default' => 'active', 'required' => true];
$roleOptions = [];
foreach ($roles as $role) $roleOptions[$role['id']] = $role['name'];
$fields = [
    'name' => ['label' => 'Full name', 'required' => true, 'maxlength' => 120, 'autocomplete' => 'name'],
    'email' => ['label' => 'Email', 'type' => 'email', 'required' => true, 'maxlength' => 190, 'autocomplete' => 'email'],
];
if (!$editing) {
    $fields['password'] = ['label' => 'Password', 'type' => 'password', 'required' => true, 'minlength' => 8, 'maxlength' => 72, 'autocomplete' => 'new-password'];
    $fields['password_confirmation'] = ['label' => 'Confirm password', 'type' => 'password', 'required' => true, 'minlength' => 8, 'maxlength' => 72, 'autocomplete' => 'new-password'];
}
$fields['role_id'] = ['label' => 'Role', 'type' => 'select', 'options' => $roleOptions, 'required' => true];
$fields['status'] = $statusField;
require dirname(__DIR__).'/partials/crud-form.php';