<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

final class AuditService
{
    public static function record(int $actorId, string $action, string $entity, int $id, ?array $old, ?array $new): void
    {
        $s = Database::connection()->prepare('INSERT INTO audit_logs(user_id,action,entity,entity_id,old_values,new_values,ip_address,user_agent) VALUES(?,?,?,?,?,?,?,?)');
        $s->execute([$actorId, $action, $entity, $id, $old === null ? null : json_encode($old),
            $new === null ? null : json_encode($new), $_SERVER['REMOTE_ADDR'] ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]);
    }
}