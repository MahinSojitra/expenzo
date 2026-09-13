<div class="page-header">
    <div><h1 class="h3 mb-1"><?=e($heading)?></h1><p class="text-muted mb-0"><?=e($subtitle ?? '')?></p></div>
    <?php if (!empty($backUrl)): ?><a class="action-button action-button--neutral btn btn-outline-secondary" href="<?=e(url($backUrl))?>"><i data-feather="arrow-left" aria-hidden="true"></i><?=e($backLabel ?? 'Back')?></a><?php endif; ?>
    <?php if (!empty($actionUrl)): ?><a class="action-button action-button--success btn btn-primary" href="<?=e(url($actionUrl))?>"><i data-feather="plus-circle" aria-hidden="true"></i><?=e(ltrim($actionLabel, "+ "))?></a><?php endif; ?>
</div>