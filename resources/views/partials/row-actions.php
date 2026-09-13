<div class="table-actions">
    <?php if ($mayEdit): ?><a class="btn btn-sm btn-outline-primary" href="<?=e(url($module.'/'.$rowId.'/edit'))?>">Edit<span class="visually-hidden"> <?=e($rowName)?></span></a><?php endif; ?>
    <?php if ($module === 'categories' && ($mayAppearance ?? false)): ?><a class="btn btn-sm btn-outline-secondary" href="<?=e(url('categories/'.$rowId.'/appearance'))?>">Appearance<span class="visually-hidden"> for <?=e($rowName)?></span></a><?php endif; ?>
    <?php if ($mayDelete): ?>
    <form method="post" action="<?=e(url($module.'/'.$rowId.'/delete'))?>" onsubmit="return confirm('Delete this record? This cannot be undone.');">
        <?=csrf_field()?><button type="submit" class="btn btn-sm btn-outline-danger">Delete<span class="visually-hidden"> <?=e($rowName)?></span></button>
    </form>
    <?php endif; ?>
    <?php if (!$mayEdit && !$mayDelete && !($module === 'categories' && ($mayAppearance ?? false))): ?><span class="text-muted">&mdash;</span><?php endif; ?>
</div>