<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Middleware\PermissionMiddleware;
use App\Repositories\AccountRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\UserRepository;
use App\Services\ReportService;

final class ReportController
{
    public function index(Request $request): void
    {
        PermissionMiddleware::require('reports.view');
        $this->ensurePermissions();
        $filters = $request->all();
        $service = new ReportService();
        $rows = $service->expenses($filters, auth_user());
        $analytics = $this->canUse('reports.analytics') ? $service->analytics($filters, auth_user()) : [];
        $categories = can('categories.view_all') ? (new CategoryRepository())->all(['status' => 'active']) : (new CategoryRepository())->allForUser((int)auth_user()['id'], true);
        $accounts = (new AccountRepository())->allVisible(auth_user());
        $users = can('finance.view_all') ? (new UserRepository())->all('', 1, 1000, auth_user())['rows'] : [];
        View::render('reports/index', compact('rows', 'filters', 'categories', 'accounts', 'users', 'analytics'));
    }

    public function csv(Request $request): void
    {
        if (!$this->canUse('reports.export_csv')) $this->deny();
        $rows = (new ReportService())->expenses($request->all(), auth_user());
        $this->csvResponse('expense-report.csv', ['Date', 'Amount', 'Category', 'Account', 'User', 'Description'], array_map(static fn($row) => [
            display_date($row['expense_date']),
            number_format((float)$row['amount'], 2, '.', ''),
            $row['category'],
            $row['account'],
            $row['user_name'] ?? '',
            $row['description'],
        ], $rows));
    }

    public function insightsCsv(Request $request): void
    {
        if (!$this->canUse('reports.export_csv') || !$this->canUse('reports.analytics')) $this->deny();
        $this->csvResponse('expense-insights.csv', [], (new ReportService())->insightRows($request->all(), auth_user()));
    }

    public function chart(Request $request): void
    {
        if (!$this->canUse('reports.export_visuals') || !$this->canUse('reports.analytics')) $this->deny();
        Response::json((new ReportService())->analytics($request->all(), auth_user()));
    }

    private function csvResponse(string $filename, array $headers, array $rows): never
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        $out = fopen('php://output', 'w');
        if ($headers) fputcsv($out, $headers);
        foreach ($rows as $row) fputcsv($out, $row);
        fclose($out);
        exit;
    }

    private function canUse(string $permission): bool
    {
        return can($permission)
            || ($permission === 'reports.export_csv' && can('reports.export'))
            || ($permission === 'reports.export_visuals' && can('reports.export'));
    }

    private function deny(): never
    {
        Response::status(403);
        View::render('errors/403');
        exit;
    }

    private function ensurePermissions(): void
    {
        $pdo = Database::connection();
        $reportPermissions = [
            'reports.analytics' => 'View report summaries, insights and charts.',
            'reports.customize' => 'Change report breakdowns, chart styles and report filters.',
            'reports.export_csv' => 'Export filtered expense and insight data as CSV files.',
            'reports.export_visuals' => 'Export report charts as image files.',
        ];
        $hasDescriptionColumn = (bool)$pdo->query("SHOW COLUMNS FROM permissions LIKE 'description'")->fetch();
        foreach ($reportPermissions as $permission => $description) {
            $pdo->prepare('INSERT IGNORE INTO permissions(name) VALUES(?)')->execute([$permission]);
            if ($hasDescriptionColumn) {
                $pdo->prepare('UPDATE permissions SET description=? WHERE name=? AND description=""')->execute([$description, $permission]);
            }
        }
        $roleId = (int)(auth_user()['role_id'] ?? 0);
        if (!$roleId) return;
        $existing = $pdo->prepare('SELECT COUNT(*) FROM role_permissions rp JOIN permissions p ON p.id=rp.permission_id WHERE rp.role_id=? AND p.name IN ("reports.analytics","reports.customize","reports.export_csv","reports.export_visuals")');
        $existing->execute([$roleId]);
        $hasNewReportPermissions = (int)$existing->fetchColumn() > 0;
        $grant = $pdo->prepare('INSERT IGNORE INTO role_permissions(role_id,permission_id) SELECT ?,id FROM permissions WHERE name=?');
        if (!$hasNewReportPermissions) {
            if (can('reports.view')) foreach (['reports.analytics', 'reports.customize'] as $permission) $grant->execute([$roleId, $permission]);
            if (can('reports.export')) foreach (['reports.export_csv', 'reports.export_visuals'] as $permission) $grant->execute([$roleId, $permission]);
        }
        $current = Session::get('permissions', []);
        $addition = [];
        if (!$hasNewReportPermissions) {
            $addition = array_merge(can('reports.view') ? ['reports.analytics', 'reports.customize'] : [], can('reports.export') ? ['reports.export_csv', 'reports.export_visuals'] : []);
        }
        Session::put('permissions', array_values(array_unique(array_merge($current, $addition))));
    }
}
