<?php
$module = 'users';
$heading = $editing ? 'Edit User' : 'Add User';
$subtitle = 'Manage user details and access.';
$formAction = $editing ? $module.'/'.$record['id'].'/update' : $module;
$submitLabel = $editing ? 'Update User' : 'Save User';
$statusField = ['label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'default' => 'active', 'required' => true];
$roleOptions = ['' => 'Choose a role'];
foreach ($roles as $role) $roleOptions[$role['id']] = $role['name'];
$fields = [
    'name' => ['label' => 'Full name', 'required' => true, 'maxlength' => 120, 'autocomplete' => 'name'],
    'email' => ['label' => 'Email', 'type' => 'email', 'required' => true, 'maxlength' => 190, 'autocomplete' => 'email'],
];
if (!$editing) {
    $fields['password'] = ['label' => 'Password', 'type' => 'password', 'required' => true, 'minlength' => 8, 'maxlength' => 72, 'autocomplete' => 'new-password'];
    $fields['password_confirmation'] = ['label' => 'Confirm password', 'type' => 'password', 'required' => true, 'minlength' => 8, 'maxlength' => 72, 'autocomplete' => 'new-password'];
}
if (can('users.assign_role')) {
    $fields['role_id'] = ['label' => 'Role', 'type' => 'select', 'options' => $roleOptions, 'required' => true];
    if ($editing && !isset($roleOptions[$record['role_id']])) $formNote = 'Current role: '.($record['role_name'] ?? 'None').'. Choose an active assignable role to replace it.';
} else {
    $formNote = 'Current role: '.($record['role_name'] ?? 'None').'. You do not have permission to change role assignments.';
    if (!empty($errors['role_id'])) $formNote .= ' '.$errors['role_id'];
}
$fields['status'] = $statusField;
require dirname(__DIR__).'/partials/crud-form.php';