<section class="auth-panel" aria-labelledby="register-title">
    <div class="card auth-card auth-panel-card">
        <div class="card-body">
            <header class="auth-panel-heading">
                <h1 id="register-title">Create account</h1>
                <p>Start building a clearer picture of your spending.</p>
            </header>
            <form method="post" action="<?= url('register') ?>"><?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="register-name">Name</label>
                    <input id="register-name" class="form-control form-control-lg" type="text" name="name"
                        autocomplete="name" required autofocus placeholder="Enter your name">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="register-email">Email</label>
                    <input id="register-email" class="form-control form-control-lg" type="email" name="email"
                        autocomplete="username" required placeholder="Enter your email">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="register-password">Password</label>
                    <input id="register-password" class="form-control form-control-lg" type="password" name="password"
                        autocomplete="new-password" required minlength="6" placeholder="Create a password" data-password-toggle>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="register-password-confirmation">Confirm password</label>
                    <input id="register-password-confirmation" class="form-control form-control-lg" type="password"
                        name="password_confirmation" autocomplete="new-password" required minlength="6"
                        placeholder="Confirm your password" data-password-toggle>
                </div>
                <div class="d-grid mt-4">
                    <button class="action-button action-button--success btn btn-lg btn-primary">
                        <i data-feather="plus-circle" aria-hidden="true"></i>Create account
                    </button>
                </div>
            </form>
            <div class="auth-panel-footer">Already have an account? <a href="<?= url('login') ?>">Sign in</a></div>
        </div>
    </div>
</section>
