<?php $title = 'Page not found'; ?>
<div class="error-page-icon"><i data-feather="search" aria-hidden="true"></i></div>
<p class="error-page-code">404</p>
<h1><?=e($title)?></h1>
<p class="error-page-description">The page you are looking for could not be found.</p>
<a class="btn action-button action-button--primary" href="<?=e(url('login'))?>"><i data-feather="arrow-left" aria-hidden="true"></i>Return to app</a>