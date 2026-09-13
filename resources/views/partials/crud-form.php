<div class="crud-form">
    <?php $actionUrl = null; $backUrl = $module; $backLabel = 'Back to '.ucfirst($module); require __DIR__.'/page-header.php'; ?>
    <div class="card"><div class="card-body">
        <?php if ($errors): ?><div class="alert alert-danger" role="alert">Please correct the highlighted fields and save again.</div><?php endif; ?>
        <form method="post" action="<?=e(url($formAction))?>">
            <?=csrf_field()?>
            <div class="row"><?php require __DIR__.'/form-fields.php'; ?></div>
            <?php if (!empty($formNote)): ?><p class="text-muted"><?=e($formNote)?></p><?php endif; ?>
            <div class="form-actions">
                <a class="action-button action-button--neutral btn btn-outline-secondary" href="<?=e(url($module))?>"><i data-feather="x" aria-hidden="true"></i>Cancel</a>
                <button type="submit" class="action-button action-button--success btn btn-primary"><i data-feather="save" aria-hidden="true"></i><?=e($submitLabel)?></button>
            </div>
        </form>
    </div></div>
</div>