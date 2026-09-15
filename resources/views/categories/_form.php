<?php
$module = 'categories';
$heading = $editing ? 'Edit Category' : 'Add Category';
$global = $editing && $record['owner_id'] === null;
$ownerLabel = '';
if ($editing) {
    $ownerLabel = $global ? 'Global - available to everyone'
        : ((int) $record['owner_id'] === (int) auth_user()['id'] ? 'Me - available only to me'
            : ($record['owner_name'] ?? 'User') . (!empty($record['owner_email']) ? ' (' . $record['owner_email'] . ')' : ''));
}
$subtitle = $editing ? $ownerLabel : 'Choose who can use this category, then set its details and appearance.';
$formNote = $editing ? 'Choose who can use this category. Ownership changes must preserve access for existing expenses and budgets.' : 'Choose Global to make this category available to everyone, or select a user to make it available only to them.';
$formAction = $editing ? $module . '/' . $record['id'] . '/update' : $module;
$submitLabel = $editing ? 'Update Category' : 'Save Category';
$statusField = ['label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'default' => 'active', 'required' => true];
$fields = [
    'name' => ['label' => 'Name', 'required' => true, 'maxlength' => 120, 'wide' => true],
    'description' => ['label' => 'Description', 'type' => 'textarea', 'wide' => true],
    'icon' => ['type' => 'icon', 'label' => 'Default icon', 'default' => 'tag', 'required' => true, 'maxlength' => 80, 'help' => 'Search by name and choose an icon.'],
    'color' => ['label' => 'Default color', 'type' => 'color', 'default' => '#3b7ddd', 'required' => true],
    'status' => $statusField,
];
if ($editing) {
    $ownerValue = $global ? 'global' : (string) $record['owner_id'];
    $currentOption = ['label' => $ownerLabel, 'icon' => $global ? 'globe' : 'user'];
    if (!can('categories.assign_owner'))
        $ownerOptions = [$ownerValue => $currentOption];
    else
        $ownerOptions[$ownerValue] = $currentOption;
    $record['owner_id'] = $ownerValue;
} {
    $fields = [
        'owner_id' => [
            'label' => 'Category owner',
            'type' => 'select',
            'options' => $ownerOptions,
            'disabled' => count($ownerOptions) === 1,
            'default' => (string) auth_user()['id'],
            'required' => true,
            'wide' => true,
            'help' => $editing && !can('categories.assign_owner') ? 'Your role can edit category details but cannot change the owner.' : (can('categories.assign_owner') ? 'Search by user name or email, or choose a shared global category if permitted.' : 'Choose from the ownership options available to your role.'),
        ]
    ] + $fields;
}
if (count($ownerOptions) === 1) {
    $formNote = '';
    $fields['owner_id']['help'] = 'You do not have permission to change the owner.';
}
require dirname(__DIR__) . '/partials/crud-form.php';