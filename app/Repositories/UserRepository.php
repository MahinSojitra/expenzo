<?php
declare(strict_types=1);
namespace App\Repositories;
use App\Core\Database;
final class UserRepository {
 public function findByEmail(string $email):?array{$s=Database::connection()->prepare('SELECT * FROM users WHERE email=? LIMIT 1');$s->execute([$email]);return $s->fetch()?:null;}
 public function find(int $id):?array{$s=Database::connection()->prepare('SELECT * FROM users WHERE id=?');$s->execute([$id]);return $s->fetch()?:null;}
 public function findWithRole(int $id):?array{$pdo=Database::connection();$s=$pdo->prepare('SELECT u.*,r.name role_name FROM users u LEFT JOIN user_roles ur ON ur.user_id=u.id LEFT JOIN roles r ON r.id=ur.role_id WHERE u.id=? LIMIT 1');$s->execute([$id]);$u=$s->fetch();if(!$u)return null;$p=$pdo->prepare('SELECT p.name FROM permissions p JOIN role_permissions rp ON rp.permission_id=p.id JOIN user_roles ur ON ur.role_id=rp.role_id WHERE ur.user_id=?');$p->execute([$id]);$u['permissions']=array_column($p->fetchAll(),'name');return $u;}
 public function all(string $q='',int $page=1,int $per=10):array{$pdo=Database::connection();$where='';$params=[];if($q!==''){$where='WHERE u.name LIKE ? OR u.email LIKE ?';$params=["%$q%","%$q%"];}$cnt=$pdo->prepare('SELECT COUNT(*) FROM users u '.$where);$cnt->execute($params);$total=(int)$cnt->fetchColumn();$off=($page-1)*$per;$sql='SELECT u.*,GROUP_CONCAT(r.name SEPARATOR ", ") role_names FROM users u LEFT JOIN user_roles ur ON ur.user_id=u.id LEFT JOIN roles r ON r.id=ur.role_id '.$where.' GROUP BY u.id ORDER BY u.created_at DESC LIMIT '.(int)$per.' OFFSET '.(int)$off;$s=$pdo->prepare($sql);$s->execute($params);return ['rows'=>$s->fetchAll(),'total'=>$total,'page'=>$page,'per'=>$per];}
 public function create(array $d):int{$pdo=Database::connection();$s=$pdo->prepare('INSERT INTO users(name,email,password_hash,status,created_at,updated_at) VALUES(?,?,?,?,NOW(),NOW())');$s->execute([$d['name'],$d['email'],password_hash($d['password'],PASSWORD_DEFAULT),$d['status']??'active']);return (int)$pdo->lastInsertId();}
 public function update(int $id,array $d):void{$pdo=Database::connection();$sql='UPDATE users SET name=?,email=?,status=?,updated_at=NOW()';$params=[$d['name'],$d['email'],$d['status']];if(!empty($d['password'])){$sql.=',password_hash=?';$params[]=password_hash($d['password'],PASSWORD_DEFAULT);} $params[]=$id;$pdo->prepare($sql.' WHERE id=?')->execute($params);}
 public function setRole(int $userId,int $roleId):void{$pdo=Database::connection();$pdo->prepare('DELETE FROM user_roles WHERE user_id=?')->execute([$userId]);$pdo->prepare('INSERT INTO user_roles(user_id,role_id) VALUES(?,?)')->execute([$userId,$roleId]);}
 public function roleIdByName(string $name):?int{$s=Database::connection()->prepare('SELECT id FROM roles WHERE name=? LIMIT 1');$s->execute([$name]);$id=$s->fetchColumn();return $id?(int)$id:null;}
 public function roles():array{return Database::connection()->query('SELECT * FROM roles ORDER BY name')->fetchAll();}
}
