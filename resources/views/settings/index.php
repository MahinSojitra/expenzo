<?php
$heading = 'Settings';
$subtitle = 'Configure application preferences.';
$actionUrl = null;
$record = $settings;
$errors = [];
?>
<div class="crud-form">
<?php require dirname(__DIR__).'/partials/page-header.php'; ?>
<div class="card"><div class="card-body">
<form method="post" action="<?=e(url('settings'))?>">
<?=csrf_field()?>
<fieldset <?=can('settings.edit') ? '' : 'disabled'?>>
<h2 class="h5 mb-3">General</h2>
<div class="row">
<?php
$fields = [
    'app_name' => ['label' => 'Application name', 'default' => APP_NAME],
    'currency' => ['label' => 'Currency', 'type' => 'select', 'options' => ['INR' => 'INR', 'USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP'], 'default' => 'INR'],
    'date_format' => ['label' => 'Date format', 'default' => 'Y-m-d'],
    'timezone' => ['label' => 'Timezone', 'default' => 'Asia/Kolkata'],
];
require dirname(__DIR__).'/partials/form-fields.php';
?>
</div>
<h2 class="h5 mt-2 mb-3">Lists and budget alerts</h2>
<div class="row">
<?php
$fields = [
    'pagination_size' => ['label' => 'Pagination size', 'type' => 'number', 'default' => 10],
    'budget_warning_thresholds' => ['label' => 'Budget warning thresholds', 'default' => '75,90,100'],
];
require dirname(__DIR__).'/partials/form-fields.php';
?>
</div>
<?php if (can('settings.edit')): ?><div class="form-actions"><button class="btn btn-primary" type="submit">Save Settings</button></div><?php endif; ?>
</fieldset>
</form>
</div></div>
</div>