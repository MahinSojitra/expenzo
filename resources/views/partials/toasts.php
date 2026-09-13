<?php
$toastMessages = [];
foreach ([
    'success' => ['Success', 'success', 'check-circle'],
    'error' => ['Error', 'danger', 'alert-circle'],
    'info' => ['Info', 'primary', 'info'],
] as $key => [$label, $style, $icon]) {
    if ($message = flash($key)) {
        $toastMessages[] = ['label' => $label, 'style' => $style, 'icon' => $icon, 'message' => $message];
    }
}
?>
<?php if ($toastMessages): ?>
<div class="toast-container app-toast-container position-fixed bottom-0 end-0 p-3">
    <?php foreach ($toastMessages as $toast): ?>
    <div class="toast app-toast app-toast-<?= e($toast['style']) ?>" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
        <div class="app-toast-content">
            <span class="app-toast-icon" aria-hidden="true"><i data-feather="<?= e($toast['icon']) ?>"></i></span>
            <div class="app-toast-copy">
                <div class="app-toast-title"><?= e($toast['label']) ?></div>
                <div class="app-toast-message"><?= e($toast['message']) ?></div>
            </div>
            <button type="button" class="btn-close app-toast-close" data-bs-dismiss="toast" aria-label="Dismiss"></button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
