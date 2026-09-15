<section class="auth-panel" aria-labelledby="login-title">
    <div class="card auth-card auth-panel-card">
        <div class="card-body">
            <header class="auth-panel-heading">
                <h1 id="login-title">Sign in</h1>
                <p>Your everyday finances, all in one place.</p>
            </header>
            <form method="post" action="<?= url('login') ?>"><?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="login-email">Email</label>
                    <input id="login-email" class="form-control form-control-lg" type="email" name="email"
                        autocomplete="username" required autofocus placeholder="Enter your email">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="login-password">Password</label>
                    <input id="login-password" class="form-control form-control-lg" type="password" name="password"
                        autocomplete="current-password" required placeholder="Enter your password" data-password-toggle>
                </div>
                <div class="form-check">
                    <input id="remember" type="checkbox" class="form-check-input" name="remember" value="1">
                    <label for="remember" class="form-check-label text-small">Remember me</label>
                </div>
                <div class="d-grid mt-4">
                    <button class="action-button action-button--primary btn btn-lg btn-primary">
                        <i data-feather="log-in" aria-hidden="true"></i>Sign in
                    </button>
                </div>
            </form>
            <div class="auth-panel-footer">No account yet? <a href="<?= url('register') ?>">Create one</a></div>
        </div>
    </div>
</section>
