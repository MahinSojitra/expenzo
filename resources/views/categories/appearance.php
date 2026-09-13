<?php
$module = 'categories';
$heading = 'Customize Appearance';
$subtitle = 'Personal badge, icon, and color for ' . $record['name'] . '. These settings apply only to you.';
$formAction = 'categories/'.$record['id'].'/appearance';
$submitLabel = 'Save Appearance';
$record['icon'] = $record['display_icon'];
$record['color'] = $record['display_color'];
$fields = [
    'badge' => ['label' => 'Badge', 'maxlength' => 16, 'wide' => true, 'help' => 'Optional emoji or short text, up to 16 characters.'],
    'icon' => ['type' => 'icon', 'label' => 'Icon', 'required' => true, 'maxlength' => 80, 'help' => 'Search by name and choose an icon.'],
    'color' => ['label' => 'Color', 'type' => 'color', 'required' => true],
];
require dirname(__DIR__).'/partials/crud-form.php';