<div class="auth-brand">
    <div class="auth-brand-mark"><i data-feather="trending-up" aria-hidden="true"></i></div>
    <div class="auth-brand-name"><?= e(APP_NAME) ?></div>
    <p class="auth-brand-tagline">Spend smart. Live better.</p>
    <!-- <p>Start managing expenses, accounts, budgets and reports in one place.</p> -->
</div>
<div class="text-center auth-heading">
    <h1 class="h2">Create account</h1>
    <p class="lead">Sign up for your <?= e(APP_NAME) ?> account</p>
</div>
<div class="card auth-card">
    <div class="card-body">
        <div class="m-sm-3">
            <form method="post" action="<?= url('register') ?>"><?= csrf_field() ?>
                <div class="mb-3"><label class="form-label">Name</label><input class="form-control form-control-lg"
                        type="text" name="name" required autofocus placeholder="Enter your name"></div>
                <div class="mb-3"><label class="form-label">Email</label><input class="form-control form-control-lg"
                        type="email" name="email" required placeholder="Enter your email"></div>
                <div class="mb-3"><label class="form-label">Password</label><input class="form-control form-control-lg"
                        type="password" name="password" required minlength="6" placeholder="Create a password"
                        data-password-toggle></div>
                <div class="mb-3"><label class="form-label">Confirm password</label><input
                        class="form-control form-control-lg" type="password" name="password_confirmation" required
                        minlength="6" placeholder="Confirm your password" data-password-toggle></div>
                <div class="d-grid gap-2 mt-3"><button
                        class="action-button action-button--success btn btn-lg btn-primary"><i
                            data-feather="plus-circle" aria-hidden="true"></i>Create account</button></div>
            </form>
        </div>
    </div>
</div>
<div class="text-center mb-3 text-muted">Already have an account? <a href="<?= url('login') ?>">Sign in</a></div>