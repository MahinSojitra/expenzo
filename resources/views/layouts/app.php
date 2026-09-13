<?php
$sidebarPath = rtrim((new \App\Core\Request())->path(), '/') ?: '/';
$navigation = [
    'Overview' => ['dashboard' => ['Dashboard', 'sliders', 'dashboard.view']],
    'Finance' => [
        'expenses' => ['Expenses', 'credit-card', 'expenses.view'],
        'categories' => ['Categories', 'tag', 'categories.view'],
        'accounts' => ['Accounts', 'briefcase', 'accounts.view'],
        'budgets' => ['Budgets', 'pie-chart', 'budgets.view'],
        'reports' => ['Reports', 'bar-chart-2', 'reports.view'],
    ],
    'Administration' => [
        'users' => ['Users', 'users', 'users.view'],
        'roles' => ['Roles & Permissions', 'shield', 'roles.view'],
        'settings' => ['Settings', 'settings', 'settings.view'],
    ],
];
$currentUser = auth_user() ?? [];
$currentUserName = $currentUser['name'] ?? 'User';
$currentUserEmail = $currentUser['email'] ?? '';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? APP_NAME) ?></title>
    <link href="<?= asset('app.css') ?>" rel="stylesheet">
    <link href="<?= asset('crud.css') ?>" rel="stylesheet">
    <style>
        .brand-mini {
            font-weight: 700
        }

        .table td,
        .table th {
            vertical-align: middle
        }

        .stat-value {
            font-size: 1.6rem;
            font-weight: 600
        }

        .receipt-thumb {
            max-width: 80px;
            max-height: 80px
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <nav id="sidebar" class="sidebar js-sidebar">
            <div class="sidebar-content js-simplebar">
                <a class="sidebar-brand" href="<?= e(url(landing_path())) ?>"><span
                        class="align-middle brand-mini"><?= e(APP_NAME) ?></span></a>
                <ul class="sidebar-nav">
                    <?php foreach ($navigation as $group => $items):
                        $visible = array_filter($items, static fn(array $item): bool => can($item[2]));
                        if (!$visible)
                            continue;
                        ?>
                        <li class="sidebar-header"><?= e($group) ?></li>
                        <?php foreach ($visible as $section => [$label, $icon, $permission]):
                            $active = $sidebarPath === '/' . $section || str_starts_with($sidebarPath, '/' . $section . '/') || ($section === 'dashboard' && $sidebarPath === '/');
                            ?>
                            <li class="sidebar-item <?= $active ? 'active' : '' ?>"><a class="sidebar-link"
                                    href="<?= e(url($section)) ?>"><i class="align-middle" data-feather="<?= e($icon) ?>"></i><span
                                        class="align-middle"><?= e($label) ?></span></a></li>
                        <?php endforeach; endforeach; ?>
                </ul>
            </div>
        </nav>
        <div class="main">
            <nav class="navbar navbar-expand navbar-light navbar-bg">
                <a class="sidebar-toggle js-sidebar-toggle" role="button" tabindex="0" aria-label="Toggle navigation"><i
                        class="hamburger align-self-center"></i></a>
                <div class="navbar-collapse collapse">
                    <ul class="navbar-nav navbar-align">
                        <li class="nav-item dropdown">
                            <a class="nav-link user-menu-toggle dropdown-toggle" href="#" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <span class="user-menu-avatar"><i data-feather="user"></i></span>
                                <span class="user-menu-name"><?= e($currentUserName) ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end user-menu-dropdown">
                                <div class="user-menu-header">
                                    <span class="user-menu-avatar user-menu-avatar-lg"><i data-feather="user"></i></span>
                                    <div class="user-menu-meta">
                                        <div class="user-menu-fullname"><?= e($currentUserName) ?></div>
                                        <?php if ($currentUserEmail !== ''): ?><div class="user-menu-email"><?= e($currentUserEmail) ?></div><?php endif; ?>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <?php if (can('settings.view')): ?>
                                <a class="dropdown-item" href="<?= url('settings') ?>"><i data-feather="settings" class="me-2"></i>Settings</a>
                                <?php endif; ?>
                                <a class="dropdown-item" href="<?= url('logout') ?>"><i data-feather="log-out" class="me-2"></i>Logout</a>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>
            <main class="content">
                <div class="container-fluid p-0">
                    <?= $content ?>
                </div>
            </main>
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row text-muted">
                        <div class="col-6 text-start">
                            <p class="mb-0"><strong><?= e(APP_NAME) ?></strong> &middot; <?= APP_VERSION ?></p>
                        </div>
                        <div class="col-6 text-end">All Rights Reserved &copy; <?= date('Y') ?></div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <?php require dirname(__DIR__).'/partials/toasts.php'; ?>
    <script src="<?= asset('app.js') ?>"></script>
    <script src="<?= asset('icon-picker.js') ?>"></script>
    <script src="<?= asset('permissions.js') ?>"></script>
    <script src="<?= asset('password-toggle.js') ?>"></script>
    <script src="<?= asset('toasts.js') ?>"></script>
    <script>document.addEventListener('DOMContentLoaded', () => { if (window.feather) feather.replace(); });</script>
    <?php if (!empty($scripts)): ?>    <?= $scripts ?><?php endif; ?>
</body>

</html>
