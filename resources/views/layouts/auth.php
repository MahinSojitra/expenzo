<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? APP_NAME) ?></title>
    <link href="<?= asset('app.css') ?>" rel="stylesheet">
    <link href="<?= asset('crud.css') ?>" rel="stylesheet">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <aside class="auth-intro" aria-labelledby="auth-intro-title">
            <div class="auth-identity">
                <span class="auth-identity-mark"><i data-feather="trending-up" aria-hidden="true"></i></span>
                <div>
                    <div class="auth-identity-name"><?= e(APP_NAME) ?></div>
                    <p>Spend smart. Live better.</p>
                </div>
            </div>
            <div class="auth-intro-content">
                <p class="auth-eyebrow">A little clarity. Every day.</p>
                <h2 id="auth-intro-title" data-auth-typewriter>Make sense of <br>your <span>money.</span></h2>
                <p class="auth-intro-description">From your morning coffee to your monthly budget, bring your everyday finances into one clear view.</p>
                <ul class="auth-features">
                    <li>
                        <span class="auth-feature-icon"><i data-feather="credit-card" aria-hidden="true"></i></span>
                        <div><h3>Know where it goes</h3><p>Keep expenses organized by account and category.</p></div>
                    </li>
                    <li>
                        <span class="auth-feature-icon"><i data-feather="target" aria-hidden="true"></i></span>
                        <div><h3>Give your spending a plan</h3><p>Set budgets and follow your progress.</p></div>
                    </li>
                    <li>
                        <span class="auth-feature-icon"><i data-feather="bar-chart-2" aria-hidden="true"></i></span>
                        <div><h3>See the bigger picture</h3><p>Explore reports to understand your spending habits.</p></div>
                    </li>
                </ul>
            </div>
            <p class="auth-intro-note">Less guesswork. More room for what matters.</p>
        </aside>
        <div class="auth-form-region"><?= $content ?></div>
    </main>
    <?php require dirname(__DIR__) . '/partials/toasts.php'; ?>
    <script src="<?= asset('app.js') ?>"></script>
    <script src="<?= asset('password-toggle.js') ?>"></script>
    <script src="<?= asset('toasts.js') ?>"></script>
    <script src="<?= asset('auth-typewriter.js') ?>"></script>
</body>
</html>
