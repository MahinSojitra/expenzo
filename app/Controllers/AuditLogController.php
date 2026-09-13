<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Middleware\PermissionMiddleware;
use App\Repositories\AuditLogRepository;

final class AuditLogController
{
    public function index(Request $request): void
    {
        PermissionMiddleware::require('audit.view');

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'action' => trim((string) $request->query('action', '')),
            'entity' => trim((string) $request->query('entity', '')),
            'user_id' => trim((string) $request->query('user_id', '')),
            'from' => trim((string) $request->query('from', '')),
            'to' => trim((string) $request->query('to', '')),
        ];

        $page = max(1, (int) $request->query('page', 1));
        $repository = new AuditLogRepository();
        $data = $repository->paginate($filters, $page, 15);
        $options = $repository->filterOptions();

        View::render('audit/index', compact('data', 'filters', 'options'));
    }
}
