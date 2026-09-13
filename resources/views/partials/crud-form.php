<div class="crud-form">
    <a class="d-inline-block mb-3" href="<?=e(url($module))?>">&larr; Back to <?=e(ucfirst($module))?></a>
    <?php $actionUrl = null; require __DIR__.'/page-header.php'; ?>
    <div class="card"><div class="card-body">
        <?php if ($errors): ?><div class="alert alert-danger" role="alert">Please correct the highlighted fields and save again.</div><?php endif; ?>
        <form method="post" action="<?=e(url($formAction))?>">
            <?=csrf_field()?>
            <div class="row"><?php require __DIR__.'/form-fields.php'; ?></div>
            <?php if (!empty($formNote)): ?><p class="text-muted"><?=e($formNote)?></p><?php endif; ?>
            <div class="form-actions">
                <a class="btn btn-outline-secondary" href="<?=e(url($module))?>">Cancel</a>
                <button type="submit" class="btn btn-primary"><?=e($submitLabel)?></button>
            </div>
        </form>
    </div></div>
</div>