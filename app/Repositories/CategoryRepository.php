<?php
declare(strict_types=1);
namespace App\Repositories; use App\Core\Database;
final class CategoryRepository {
 public function allForUser(int $userId,bool $activeOnly=false):array{$sql='SELECT c.*,ucs.badge,COALESCE(ucs.icon,c.icon) display_icon,COALESCE(ucs.color,c.color) display_color FROM categories c LEFT JOIN user_category_settings ucs ON ucs.category_id=c.id AND ucs.user_id=?';if($activeOnly)$sql.=' WHERE c.status="active"';$sql.=' ORDER BY c.name';$s=Database::connection()->prepare($sql);$s->execute([$userId]);return $s->fetchAll();}
 public function all():array{return Database::connection()->query('SELECT c.*,NULL badge,c.icon display_icon,c.color display_color FROM categories c ORDER BY c.name')->fetchAll();}
 public function find(int $id):?array{$s=Database::connection()->prepare('SELECT * FROM categories WHERE id=?');$s->execute([$id]);return $s->fetch()?:null;}
 public function create(array $d,?int $actorId=null):int{$s=Database::connection()->prepare('INSERT INTO categories(name,description,icon,color,status,created_by,created_at,updated_at) VALUES(?,?,?,?,?,?,NOW(),NOW())');$s->execute([trim((string)$d['name']),$d['description']??null,$d['icon']??'tag',$d['color']??'#3b7ddd',$d['status']??'active',$actorId]);return (int)Database::connection()->lastInsertId();}
 public function update(int $id,array $d):void{$s=Database::connection()->prepare('UPDATE categories SET name=?,description=?,icon=?,color=?,status=?,updated_at=NOW() WHERE id=?');$s->execute([trim((string)$d['name']),$d['description']??null,$d['icon']??'tag',$d['color']??'#3b7ddd',$d['status']??'active',$id]);}
 public function delete(int $id):void{Database::connection()->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);}
 public function customize(int $categoryId,int $userId,array $d):void{$s=Database::connection()->prepare('INSERT INTO user_category_settings(user_id,category_id,badge,icon,color,created_at,updated_at) VALUES(?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE badge=VALUES(badge),icon=VALUES(icon),color=VALUES(color),updated_at=NOW()');$s->execute([$userId,$categoryId,trim((string)($d['badge']??''))?:null,$d['icon']??null,$d['color']??null]);}
}
