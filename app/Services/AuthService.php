<?php
declare(strict_types=1);
namespace App\Services; use App\Core\Session; use App\Repositories\UserRepository;
final class AuthService { public function login(string $email,string $password,bool $remember=false):bool{$repo=new UserRepository();$u=$repo->findByEmail($email);if(!$u||$u['status']!=='active'||!password_verify($password,$u['password_hash']))return false;Session::regenerate();$full=$repo->findWithRole((int)$u['id']);Session::put('user_id',(int)$u['id']);Session::put('user',$full);Session::put('permissions',$full['permissions']??[]);Session::put('remember',$remember);return true;} public function logout():void{Session::destroy();}}
