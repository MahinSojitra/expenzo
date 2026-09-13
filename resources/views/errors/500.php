<?php $title = 'Something went wrong'; ?>
<div class="error-page-icon"><i data-feather="alert-triangle" aria-hidden="true"></i></div>
<p class="error-page-code">500</p>
<h1><?=e($title)?></h1>
<p class="error-page-description">We could not complete your request. Please try again later.</p>
<a class="btn action-button action-button--primary" href="<?=e(url('login'))?>"><i data-feather="arrow-left" aria-hidden="true"></i>Return to app</a>