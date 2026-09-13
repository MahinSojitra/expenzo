<?php $title = 'Access denied'; ?>
<div class="error-page-icon"><i data-feather="shield-off" aria-hidden="true"></i></div>
<p class="error-page-code">403</p>
<h1><?=e($title)?></h1>
<p class="error-page-description">You do not have permission to access this page.</p>
<a class="btn action-button action-button--primary" href="<?=e(url('login'))?>"><i data-feather="arrow-left" aria-hidden="true"></i>Return to app</a>