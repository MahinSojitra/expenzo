<?php
declare(strict_types=1);
// Temporary fixtures are rolled back, including when a check fails.
require dirname(__DIR__) . '/app/Core/Autoloader.php';
App\Core\Env::load(dirname(__DIR__) . '/.env');
use App\Core\Database;
use App\Repositories\CategoryRepository;
use App\Services\FieldValidationException;

$pdo = Database::connection();
$repo = new CategoryRepository();
$checks = 0;
function check(bool $ok, string $message): void
{
    global $checks;
    if (!$ok)
        throw new RuntimeException($message);
    $checks++;
}
function denied(callable $action): void
{
    try {
        $action();
    } catch (FieldValidationException $e) {
        check($e->field === 'owner_id', 'Ownership errors must target the owner field.');
        return;
    }
    throw new RuntimeException('Unauthorized or invalid owner was accepted.');
}
$pdo->beginTransaction();
try {
    $suffix = bin2hex(random_bytes(8));
    $insert = $pdo->prepare('INSERT INTO users(name,email,password_hash,status) VALUES(?,?,?,?)');
    $ids = [];
    foreach (['active', 'active', 'inactive'] as $i => $status) {
        $insert->execute(['Owner test ' . $i, 'owner-' . $suffix . '-' . $i . '@example.test', 'unused', $status]);
        $ids[] = (int) $pdo->lastInsertId();
    }
    $personal = ['id' => $ids[0], 'permissions' => ['categories.create']];
    $admin = ['id' => $ids[0], 'permissions' => ['categories.create', 'categories.assign_owner', 'categories.manage_global']];
    $assigner = ['id' => $ids[0], 'permissions' => ['categories.create', 'categories.assign_owner']];
    $globalOnly = ['id' => $ids[0], 'permissions' => ['categories.create', 'categories.manage_global']];
    $base = ['name' => 'Ownership ' . $suffix, 'description' => '', 'icon' => 'tag', 'color' => '#123456', 'status' => 'active'];
    check(count($repo->ownerOptions($personal)) === 1, 'Personal users must only see themselves.');
    check(!isset($repo->ownerOptions($assigner)['global']), 'Assignment permission must not imply global access.');
    check(count($repo->ownerOptions($globalOnly)) === 2, 'Global permission must not expose other users.');
    check(isset($repo->ownerOptions($admin)[$ids[1]]), 'User without categories must appear in owner choices.');
    check(str_contains($repo->ownerOptions($admin)[$ids[2]]['label'], 'Inactive'), 'Inactive owners need a clear label.');
    $ownId = $repo->create($base, $admin);
    check((int) $repo->find($ownId)['owner_id'] === $ids[0], 'Default ownership must be personal, including for admins.');
    $globalId = $repo->create($base + ['owner_id' => 'global'], $admin);
    check($repo->find($globalId)['owner_id'] === null, 'Global ownership must be null.');
    $otherId = $repo->create($base + ['owner_id' => (string) $ids[1]], $assigner);
    check((int) $repo->find($otherId)['owner_id'] === $ids[1], 'Selected user must own the category.');
    check((int) $repo->find($otherId)['created_by'] === $ids[0], 'Creator must remain the acting admin.');
    check($repo->findForUser($otherId, $ids[1]) !== null, 'Owner must see assigned category.');
    check($repo->findForUser($otherId, $ids[0]) === null, 'Assigned category must not leak into creator personal options.');
    check($repo->findForUser($globalId, $ids[2]) !== null, 'Global category must be available to everyone.');
    $inactiveId = $repo->create($base + ['owner_id' => $ids[2]], $admin);
    check((int) $repo->find($inactiveId)['owner_id'] === $ids[2], 'Admin can prepare categories for inactive users.');
    denied(fn() => $repo->create($base + ['owner_id' => $ids[1]], $personal));
    denied(fn() => $repo->create($base + ['owner_id' => 'global'], $personal));
    denied(fn() => $repo->create($base + ['owner_id' => 'global'], $assigner));
    denied(fn() => $repo->create($base + ['owner_id' => $ids[1]], $globalOnly));
    denied(fn() => $repo->create($base, ['id' => $ids[0], 'permissions' => ['categories.assign_owner']]));
    foreach (['', '0', '-1', '1.5', '123abc', [], '99999999999999999999999', '2147483647'] as $bad) {
        denied(fn() => $repo->create($base + ['owner_id' => $bad], $admin));
    }
    try {
        $repo->create($base + ['owner_id' => $ids[1]], $admin);
        throw new RuntimeException('Duplicate owner/name was accepted.');
    } catch (PDOException $e) {
        check(($e->errorInfo[1] ?? 0) === 1062, 'Duplicate must retain existing uniqueness validation.');
    }
    $admin['permissions'][] = 'categories.edit';
    $admin['permissions'][] = 'categories.manage_all';
    $personal['permissions'][] = 'categories.edit';
    denied(fn() => $repo->update($ownId, $base + ['owner_id' => 'global'], $personal));
    $changed = array_replace($base, ['name' => 'Transferred ' . $suffix, 'owner_id' => 'global']);
    $repo->update($ownId, $changed, $admin);
    check($repo->find($ownId)['owner_id'] === null, 'Admin can make a category global.');
    $changed['owner_id'] = (string) $ids[1];
    $repo->update($ownId, $changed, $admin);
    check((int) $repo->find($ownId)['owner_id'] === $ids[1], 'Admin can assign an unused category to another user.');
    check((int) $repo->find($ownId)['created_by'] === $ids[0], 'Transfer preserves the original creator.');
    denied(fn() => $repo->update($ownId, $changed, $personal));
    $pdo->prepare('INSERT INTO budgets(user_id,category_id,start_date,end_date,budget_amount,warning_threshold,status) VALUES(?,?,CURDATE(),CURDATE(),100,80,"active")')->execute([$ids[1], $ownId]);
    $changed['owner_id'] = (string) $ids[0];
    denied(fn() => $repo->update($ownId, $changed, $admin));
    check((int) $repo->find($ownId)['owner_id'] === $ids[1], 'Blocked transfer preserves ownership.');
    $changed['owner_id'] = 'global';
    $repo->update($ownId, $changed, $admin);
    check($repo->find($ownId)['owner_id'] === null, 'Global sharing preserves existing budget access.');
    $changed['owner_id'] = [];
    denied(fn() => $repo->update($ownId, $changed, $admin));
    echo $checks . " category ownership checks passed.\n";
} finally {
    $pdo->rollBack();
}