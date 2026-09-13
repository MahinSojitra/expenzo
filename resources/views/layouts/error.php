<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=e($title ?? 'Error')?> | <?=e(APP_NAME)?></title>
    <link href="<?=asset('app.css')?>" rel="stylesheet">
    <link href="<?=asset('crud.css')?>" rel="stylesheet">
</head>
<body class="error-page">
    <main class="error-page-main">
        <div class="error-page-content"><?=$content?></div>
    </main>
    <script src="<?=asset('app.js')?>"></script>
    <script>if (window.feather) window.feather.replace();</script>
</body>
</html>