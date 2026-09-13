<div class="table-actions">
    <?php if ($mayEdit): ?><a class="action-button action-button--primary btn btn-sm btn-outline-primary" href="<?=e(url($module.'/'.$rowId.'/edit'))?>"><i data-feather="edit-3" aria-hidden="true"></i>Edit<span class="visually-hidden"> <?=e($rowName)?></span></a><?php endif; ?>
    <?php if ($module === 'categories' && ($mayAppearance ?? false)): ?><a class="action-button action-button--purple btn btn-sm btn-outline-secondary" href="<?=e(url('categories/'.$rowId.'/appearance'))?>"><i data-feather="sliders" aria-hidden="true"></i>Appearance<span class="visually-hidden"> for <?=e($rowName)?></span></a><?php endif; ?>
    <?php if ($mayDelete): ?>
    <form method="post" action="<?=e(url($module.'/'.$rowId.'/delete'))?>" onsubmit="return confirm('Delete this record? This cannot be undone.');">
        <?=csrf_field()?><button type="submit" class="action-button action-button--danger btn btn-sm btn-outline-danger"><i data-feather="trash-2" aria-hidden="true"></i>Delete<span class="visually-hidden"> <?=e($rowName)?></span></button>
    </form>
    <?php endif; ?>
    <?php if (!$mayEdit && !$mayDelete && !($module === 'categories' && ($mayAppearance ?? false))): ?><span class="text-muted">&mdash;</span><?php endif; ?>
</div>