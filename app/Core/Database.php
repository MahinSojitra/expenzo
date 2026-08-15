<?php
declare(strict_types=1);
namespace App\Core;
use PDO; use PDOException;
final class Database {
    private static ?PDO $pdo=null;
    public static function connection(): PDO {
        if (self::$pdo) return self::$pdo;
        $c=require dirname(__DIR__,2).'/config/database.php';
        $dsn=sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',$c['host'],$c['port'],$c['database'],$c['charset']);
        self::$pdo=new PDO($dsn,$c['username'],$c['password'],[
          PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES=>false,
        ]);
        return self::$pdo;
    }
}
