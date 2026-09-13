<div class="page-header">
    <div><h1 class="h3 mb-1"><?=e($heading)?></h1><p class="text-muted mb-0"><?=e($subtitle ?? '')?></p></div>
    <?php if (!empty($actionUrl)): ?><a class="btn btn-primary" href="<?=e(url($actionUrl))?>"><?=e($actionLabel)?></a><?php endif; ?>
</div>