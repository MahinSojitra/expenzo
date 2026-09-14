<?php
$total = max(0, (int)($total ?? 0));
$per = max(1, (int)($per ?? 10));
$pages = max(1, (int)ceil($total / $per));
$page = max(1, min($pages, (int)($page ?? 1)));
$first = $total ? ($page - 1) * $per + 1 : 0;
$last = min($total, $page * $per);
$pageUrl = static fn(int $number): string => '?' . http_build_query(array_merge($_GET, ['page' => $number]));
$numbers = array_values(array_unique(array_merge([1], range(max(1, $page - 1), min($pages, $page + 1)), [$pages])));
?>
<div class="list-pagination">
    <p class="list-pagination-summary"><strong><?=$first?>&ndash;<?=$last?></strong> of <strong><?=$total?></strong> <?=$total === 1 ? 'result' : 'results'?> <span>&middot; <?=$per?> per page</span></p>
    <?php if ($pages > 1): ?>
    <nav aria-label="Results pages">
        <ul class="pagination">
            <li class="page-item <?=$page === 1 ? 'disabled' : ''?>">
                <?php if ($page === 1): ?><span class="page-link" aria-disabled="true"><i data-feather="chevron-left" aria-hidden="true"></i><span>Prev</span></span>
                <?php else: ?><a class="page-link" rel="prev" href="<?=e($pageUrl($page - 1))?>"><i data-feather="chevron-left" aria-hidden="true"></i><span>Prev</span></a><?php endif; ?>
            </li>
            <?php $previous = 0; foreach ($numbers as $number): ?>
                <?php if ($previous && $number - $previous > 1): ?><li class="page-item"><span class="pagination-gap" aria-label="More pages">&hellip;</span></li><?php endif; ?>
                <li class="page-item <?=$number === $page ? 'active' : ''?>">
                    <?php if ($number === $page): ?><span class="page-link" aria-current="page"><?=$number?></span>
                    <?php else: ?><a class="page-link" aria-label="Page <?=$number?>" href="<?=e($pageUrl($number))?>"><?=$number?></a><?php endif; ?>
                </li>
            <?php $previous = $number; endforeach; ?>
            <li class="page-item <?=$page === $pages ? 'disabled' : ''?>">
                <?php if ($page === $pages): ?><span class="page-link" aria-disabled="true"><span>Next</span><i data-feather="chevron-right" aria-hidden="true"></i></span>
                <?php else: ?><a class="page-link" rel="next" href="<?=e($pageUrl($page + 1))?>"><span>Next</span><i data-feather="chevron-right" aria-hidden="true"></i></a><?php endif; ?>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>
