<?php
$heading = 'Audit Logs';
$subtitle = 'Review activity and changes across the application.';
$actionUrl = null;
require dirname(__DIR__).'/partials/page-header.php';
$actionIcons = ['create'=>'plus-circle', 'update'=>'edit-3', 'delete'=>'trash-2', 'login'=>'log-in', 'logout'=>'log-out'];
$actionColors = ['create'=>'success', 'update'=>'primary', 'delete'=>'danger', 'login'=>'info', 'logout'=>'secondary'];
$entityIcons = ['users'=>'users', 'roles'=>'shield', 'expenses'=>'credit-card', 'categories'=>'tag', 'accounts'=>'briefcase', 'budgets'=>'pie-chart', 'settings'=>'settings'];
$label = static fn($value) => ucwords(str_replace(['_', '-'], ' ', (string)$value));
$redact = static function ($value) use (&$redact) {
    if (!is_array($value)) return $value;
    foreach ($value as $key => &$item) {
        $item = preg_match('/password|token|secret/i', (string)$key) ? '[Redacted]' : $redact($item);
    }
    return $value;
};
$pretty = static function ($value) use ($redact): string {
    if ($value === null || $value === '') return 'No values recorded.';
    $decoded = json_decode((string)$value, true);
    if (json_last_error() !== JSON_ERROR_NONE) return 'Details unavailable.';
    return (string)json_encode($redact($decoded), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
};
?>
<div class="card"><div class="card-body">
<form method="get" action="<?=e(url('audit-logs'))?>" class="audit-filters">
    <div><label for="audit-search" class="form-label">Search</label><input id="audit-search" class="form-control" name="q" value="<?=e($filters['q'])?>" placeholder="Name, email, record or IP"></div>
    <div><label for="audit-action" class="form-label">Action</label><select id="audit-action" class="form-select" name="action">
        <option value="" data-icon="activity">All actions</option>
        <?php foreach ($options['actions'] as $option): $value = $option['action']; ?>
        <option value="<?=e($value)?>" data-icon="<?=e($actionIcons[$value] ?? 'activity')?>" <?=$filters['action'] === $value ? 'selected' : ''?>><?=e($label($value))?></option>
        <?php endforeach; ?>
    </select></div>
    <div><label for="audit-entity" class="form-label">Entity</label><select id="audit-entity" class="form-select" name="entity">
        <option value="" data-icon="layers">All entities</option>
        <?php foreach ($options['entities'] as $option): $value = $option['entity']; ?>
        <option value="<?=e($value)?>" data-icon="<?=e($entityIcons[$value] ?? 'box')?>" <?=$filters['entity'] === $value ? 'selected' : ''?>><?=e($label($value))?></option>
        <?php endforeach; ?>
    </select></div>
    <div><label for="audit-user" class="form-label">User</label><select id="audit-user" class="form-select" name="user_id">
        <option value="" data-icon="users">All users</option>
        <?php foreach ($options['users'] as $option): ?>
        <option value="<?=e($option['id'])?>" data-icon="user" <?=$filters['user_id'] === (string)$option['id'] ? 'selected' : ''?>><?=e($option['name'].' ('.$option['email'].')')?></option>
        <?php endforeach; ?>
    </select></div>
    <div><label for="audit-from" class="form-label">From date</label><input id="audit-from" type="date" name="from" class="form-control" value="<?=e($filters['from'])?>"></div>
    <div><label for="audit-to" class="form-label">To date</label><input id="audit-to" type="date" name="to" class="form-control" value="<?=e($filters['to'])?>"></div>
    <div class="audit-filter-actions"><button class="action-button action-button--primary btn btn-primary" type="submit"><i data-feather="filter" aria-hidden="true"></i>Filter</button><a class="action-button action-button--neutral btn btn-outline-secondary" href="<?=e(url('audit-logs'))?>"><i data-feather="rotate-ccw" aria-hidden="true"></i>Reset</a></div>
</form>
<p class="text-muted small"><?=e(number_format($data['total']))?> matching log entries</p>
<div class="table-responsive" role="region" aria-label="Audit logs" tabindex="0">
<table class="table crud-table audit-table">
<thead><tr><th>Record ID</th><th>Date &amp; time</th><th>User</th><th>Action</th><th>Entity</th><th>IP address</th><th>Details</th></tr></thead>
<tbody>
<?php if (!$data['rows']): ?><tr><td colspan="7" class="text-center text-muted py-5"><i data-feather="inbox" aria-hidden="true"></i><p class="mb-0">No audit logs match these filters.</p></td></tr><?php endif; ?>
<?php foreach ($data['rows'] as $row): ?>
<tr>
<td><?=e($row['entity_id'] ?? '—')?></td>
<td class="text-nowrap"><?=e(display_date($row['created_at'], true))?></td>
<td><div class="audit-user"><i data-feather="user" aria-hidden="true"></i><div><strong><?=e($row['user_name'] ?? ($row['user_id'] ? 'Deleted user' : 'System'))?></strong><small class="text-muted"><?=e($row['user_email'] ?? '')?></small></div></div></td>
<td><span class="audit-action audit-action--<?=e($actionColors[$row['action']] ?? 'secondary')?>"><i data-feather="<?=e($actionIcons[$row['action']] ?? 'activity')?>" aria-hidden="true"></i><?=e($label($row['action']))?></span></td>
<td><span class="audit-entity"><i data-feather="<?=e($entityIcons[$row['entity']] ?? 'box')?>" aria-hidden="true"></i><?=e($label($row['entity']))?></span></td>

<td class="text-nowrap"><?=e($row['ip_address'] ?? '—')?></td>
<td><button type="button" class="action-button action-button--primary btn btn-sm btn-outline-primary" data-audit-open="<?=e($row['id'])?>" aria-haspopup="dialog" aria-controls="audit-drawer"><i data-feather="eye" aria-hidden="true"></i>View details</button>
<template id="audit-detail-<?=e($row['id'])?>">
    <dl class="audit-drawer-meta">
        <div><dt><i data-feather="user" aria-hidden="true"></i>User</dt><dd><?=e($row['user_name'] ?? 'System')?><small><?=e($row['user_email'] ?? '')?></small></dd></div>
        <div><dt><i data-feather="calendar" aria-hidden="true"></i>Date &amp; time</dt><dd><?=e(display_date($row['created_at'], true))?></dd></div>
        <div><dt><i data-feather="<?=e($actionIcons[$row['action']] ?? 'activity')?>" aria-hidden="true"></i>Action</dt><dd><?=e($label($row['action']))?></dd></div>
        <div><dt><i data-feather="<?=e($entityIcons[$row['entity']] ?? 'box')?>" aria-hidden="true"></i>Entity / record</dt><dd><?=e($label($row['entity']))?> / <?=e($row['entity_id'] ?? '—')?></dd></div>
        <div><dt><i data-feather="globe" aria-hidden="true"></i>IP address</dt><dd><?=e($row['ip_address'] ?? 'Not recorded')?></dd></div>
    </dl>
    <section><h3><i data-feather="file-minus" aria-hidden="true"></i>Before</h3><pre><?=e($pretty($row['old_values']))?></pre></section>
    <section><h3><i data-feather="file-plus" aria-hidden="true"></i>After</h3><pre><?=e($pretty($row['new_values']))?></pre></section>
    <section><h3><i data-feather="monitor" aria-hidden="true"></i>Browser / device</h3><p><?=e($row['user_agent'] ?? 'Not recorded')?></p></section>
</template></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php $total = $data['total']; $page = $data['page']; $per = $data['per']; require dirname(__DIR__).'/partials/pagination.php'; ?>
</div></div>
<dialog id="audit-drawer" class="audit-drawer" aria-labelledby="audit-drawer-title">
    <header class="audit-drawer-header">
        <div><h2 id="audit-drawer-title"><i data-feather="activity" aria-hidden="true"></i>Audit log details</h2><p id="audit-drawer-reference"></p></div>
        <button type="button" class="audit-drawer-close" aria-label="Close audit details" autofocus><i data-feather="x" aria-hidden="true"></i></button>
    </header>
    <div class="audit-drawer-body"></div>
</dialog>
<?php ob_start(); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const drawer = document.getElementById('audit-drawer');
    if (!drawer) return;
    document.body.appendChild(drawer);
    let opener;
    document.querySelectorAll('[data-audit-open]').forEach(function (button) {
        button.addEventListener('click', function () {
            const template = document.getElementById('audit-detail-' + button.dataset.auditOpen);
            if (!template) return;
            opener = button;
            drawer.querySelector('.audit-drawer-body').replaceChildren(template.content.cloneNode(true));
            document.getElementById('audit-drawer-reference').textContent = 'Log #' + button.dataset.auditOpen;
            if (window.feather) window.feather.replace();
            drawer.showModal();
            document.body.classList.add('audit-drawer-open');
        });
    });
    drawer.querySelector('.audit-drawer-close').addEventListener('click', function () { drawer.close(); });
    drawer.addEventListener('click', function (event) {
        if (event.target === drawer && event.clientX < drawer.getBoundingClientRect().left) drawer.close();
    });
    drawer.addEventListener('close', function () {
        document.body.classList.remove('audit-drawer-open');
        if (opener) opener.focus();
    });
});
</script>
<?php $scripts = ($scripts ?? '') . ob_get_clean(); ?>