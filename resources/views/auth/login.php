<div class="auth-brand">
    <div class="auth-brand-mark"><i data-feather="trending-up" aria-hidden="true"></i></div>
    <div class="auth-brand-name"><?= e(APP_NAME) ?></div>
    <!-- <p>Simple expense tracking for daily spending, budgets and reports.</p> -->
</div>
<div class="text-center auth-heading">
    <h1 class="h2">Welcome back!</h1>
    <p class="lead">Sign in to your <?= e(APP_NAME) ?> account</p>
</div>
<div class="card auth-card">
    <div class="card-body">
        <div class="m-sm-3">
            <form method="post" action="<?= url('login') ?>"><?= csrf_field() ?>
                <div class="mb-3"><label class="form-label">Email</label><input class="form-control form-control-lg"
                        type="email" name="email" required autofocus placeholder="Enter your email"></div>
                <div class="mb-3"><label class="form-label">Password</label><input class="form-control form-control-lg"
                        type="password" name="password" required placeholder="Enter your password" data-password-toggle>
                </div>
                <div class="form-check"><input id="remember" type="checkbox" class="form-check-input" name="remember"
                        value="1"><label for="remember" class="form-check-label text-small">Remember me</label></div>
                <div class="d-grid gap-2 mt-3"><button
                        class="action-button action-button--primary btn btn-lg btn-primary"><i data-feather="log-in"
                            aria-hidden="true"></i>Sign in</button></div>
            </form>
        </div>
    </div>
</div>
<div class="text-center mb-3 text-muted">No account yet? <a href="<?= url('register') ?>">Create one</a></div>