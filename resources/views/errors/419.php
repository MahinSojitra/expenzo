<?php $title = 'Page expired'; ?>
<div class="error-page-icon"><i data-feather="clock" aria-hidden="true"></i></div>
<p class="error-page-code">419</p>
<h1><?=e($title)?></h1>
<p class="error-page-description">Your session or form has expired. Please open the page again and try again.</p>
<a class="btn action-button action-button--primary" href="<?=e(url('login'))?>"><i data-feather="arrow-left" aria-hidden="true"></i>Return to app</a>