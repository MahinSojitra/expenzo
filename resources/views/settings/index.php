<?php
$heading = 'Settings';
$subtitle = 'Configure application preferences.';
$actionUrl = null;
$record = $settings;
$errors = $errors ?? [];
?>
<div class="crud-form settings-page">
<?php require dirname(__DIR__).'/partials/page-header.php'; ?>
<div class="card"><div class="card-body">
<form method="post" action="<?=e(url('settings'))?>">
<?=csrf_field()?>
<fieldset <?=can('settings.edit') ? '' : 'disabled'?>>
<section class="settings-section" aria-labelledby="settings-general-heading">
<header class="settings-section-header"><span class="settings-section-icon"><i data-feather="settings" aria-hidden="true"></i></span><div><h2 id="settings-general-heading">General</h2><p>Application name, currency and date preferences.</p></div></header>
<div class="settings-section-fields"><div class="row">
<?php
$fields = [
    'app_name' => ['label' => 'Application name', 'default' => APP_NAME],
    'currency' => ['label' => 'Currency', 'type' => 'select', 'options' => ['INR' => 'INR', 'USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP'], 'default' => 'INR'],
    'date_format' => ['label' => 'Date format', 'default' => 'Y-m-d', 'help' => 'Use Y for year, m for month and d for day. Examples: Y-m-d → 2026-09-14; d/m/Y → 14/09/2026.'],
    'timezone' => ['label' => 'Timezone', 'default' => 'Asia/Kolkata', 'help' => 'Enter a timezone name such as Asia/Kolkata (India), Europe/London or America/New_York. Use UTC for Coordinated Universal Time.'],
];
require dirname(__DIR__).'/partials/form-fields.php';
?>
</div>
</div></section>
<section class="settings-section settings-section--alerts" aria-labelledby="settings-alerts-heading">
<header class="settings-section-header"><span class="settings-section-icon"><i data-feather="sliders" aria-hidden="true"></i></span><div><h2 id="settings-alerts-heading">Lists &amp; budget alerts</h2><p>Manage list sizes and budget warning levels.</p></div></header>
<div class="settings-section-fields"><div class="row">
<?php
$fields = [
    'pagination_size' => ['label' => 'Rows per page', 'type' => 'number', 'default' => 10, 'required' => true, 'min' => 1, 'max' => 100, 'step' => 1, 'help' => 'Show 1 to 100 rows per page across all record lists and report details.'],
    'budget_warning_thresholds' => ['label' => 'Budget warning thresholds (%)', 'prefix' => '%', 'default' => '75,90,100', 'help' => 'Separate percentages with commas, e.g. 75,90,100.'],
];
require dirname(__DIR__).'/partials/form-fields.php';
?>
</div>
</div></section>
<?php if (can('settings.edit')): ?><div class="form-actions"><button class="action-button action-button--success btn btn-primary" type="submit"><i data-feather="save" aria-hidden="true"></i>Save Settings</button></div><?php endif; ?>
</fieldset>
</form>
</div></div>
</div>
