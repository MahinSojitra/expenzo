<?php
declare(strict_types=1);
namespace App\Services; use App\Core\Database;
final class ReportService { public function expenses(array $f,int $userId):array{$where=['e.user_id=?','e.status="posted"'];$p=[$userId];foreach(['category_id','account_id'] as $k){if(($f[$k]??'')!==''){$where[]='e.'.$k.'=?';$p[]=$f[$k];}}if(!empty($f['from'])){$where[]='e.expense_date>=?';$p[]=$f['from'];}if(!empty($f['to'])){$where[]='e.expense_date<=?';$p[]=$f['to'];}$s=Database::connection()->prepare('SELECT e.expense_date,e.amount,c.name category,a.name account,e.description FROM expenses e LEFT JOIN categories c ON c.id=e.category_id LEFT JOIN accounts a ON a.id=e.account_id WHERE '.implode(' AND ',$where).' ORDER BY e.expense_date DESC');$s->execute($p);return $s->fetchAll();}}
