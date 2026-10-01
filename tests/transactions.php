<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/Core/Autoloader.php';
require dirname(__DIR__).'/app/Helpers/helpers.php';
App\Core\Env::load(dirname(__DIR__).'/.env');
$config = require dirname(__DIR__).'/config/database.php';
$name = 'expenzo_transactions_test_'.bin2hex(random_bytes(6));
$admin = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config['host'], $config['port']), $config['username'], $config['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$checks=0;
function check(bool $condition, string $message): void { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
function rejected(callable $fn): void { try { $fn(); } catch (App\Services\FieldValidationException $e) { check(true, 'rejected'); return; } throw new RuntimeException('Expected validation rejection'); }
try {
    $admin->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    putenv('DB_DATABASE='.$name);
    foreach (['migrations/001_initial.sql','seeders/001_seed.sql'] as $file) {
        $sql = file_get_contents(dirname(__DIR__).'/database/'.$file);
        $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS expenzo[^;]*;/', '', $sql);
        $admin->exec(str_replace('USE expenzo;', "USE `$name`;", $sql));
    }
    $pdo = App\Core\Database::connection();
    $uid = (int)$pdo->query("SELECT id FROM users WHERE email='user@expenzo.com'")->fetchColumn();
    $actor = (new App\Repositories\UserRepository())->findWithRole($uid);
    $repo = new App\Repositories\AccountRepository();
    $a = $repo->create(['name'=>'Test bank','type'=>'Bank','opening_balance'=>'10000'], $uid);
    $b = $repo->create(['name'=>'Test cash','type'=>'Cash','opening_balance'=>'1000'], $uid);
    $foreign = $repo->create(['name'=>'Foreign','type'=>'Bank','opening_balance'=>'100'], 1);
    $service = new App\Services\TransactionService();
    $base = ['type'=>'income','account_id'=>$a,'amount'=>'15000','transaction_date'=>date('Y-m-d'),'description'=>'Salary','submission_token'=>bin2hex(random_bytes(32))];
    $balance = fn(int $id) => $repo->findForUser($id,$uid)['current_balance'];
    $post = fn(array $changes) => $service->post(array_replace($base,['submission_token'=>bin2hex(random_bytes(32))],$changes),$actor);
    $income = $service->post($base,$actor);
    check($balance($a)==='25000.00','Income balance');
    check($service->post($base,$actor)===$income && $balance($a)==='25000.00','Duplicate submission');
    $transfer = $post(['type'=>'transfer','amount'=>'5000','destination_account_id'=>$b]);
    check($balance($a)==='20000.00' && $balance($b)==='6000.00','Transfer both balances');
    $service->reverse($transfer,'Wrong destination',$actor);
    check($balance($a)==='25000.00' && $balance($b)==='1000.00','Transfer reversal');
    rejected(fn()=>$service->reverse($transfer,'Again',$actor));
    $adjustment = $post(['type'=>'adjustment','amount'=>'200','direction'=>'decrease']);
    check($balance($a)==='24800.00','Adjustment decrease');
    $service->reverse($adjustment,'Statement corrected',$actor);
    check($balance($a)==='25000.00','Adjustment reversal');
    foreach (['0','-1','1.001','1e3','10000000000000',[],null] as $invalid) rejected(fn()=>$post(['amount'=>$invalid]));
    rejected(fn()=>$post(['type'=>'transfer','destination_account_id'=>$a]));
    rejected(fn()=>$post(['type'=>'transfer','destination_account_id'=>$foreign]));
    rejected(fn()=>$post(['account_id'=>$foreign]));
    rejected(fn()=>$post(['description'=>' ']));
    rejected(fn()=>$post(['description'=>str_repeat('a',256)]));
    rejected(fn()=>$post(['transaction_date'=>'2026-02-30']));
    rejected(fn()=>$post(['transaction_date'=>'9999-01-01']));
    rejected(fn()=>$post(['type'=>'unknown']));
    rejected(fn()=>$post(['type'=>'adjustment','direction'=>'unknown']));
    rejected(fn()=>$service->post($base,['id'=>$uid,'permissions'=>['finance.view_all']]));
    rejected(fn()=>$service->reverse($income,'No permission',['id'=>$uid,'permissions'=>['transactions.create']]));
    rejected(fn()=>$service->reverse($income,'Foreign',['id'=>1,'permissions'=>['transactions.reverse','finance.view_all']]));
    $before = $pdo->query('SELECT COUNT(*) FROM account_transactions')->fetchColumn();
    rejected(fn()=>$post(['type'=>'transfer','amount'=>'999999','destination_account_id'=>$b]));
    check($balance($a)==='25000.00' && $balance($b)==='1000.00','Failed transfer atomicity');
    check($pdo->query('SELECT COUNT(*) FROM account_transactions')->fetchColumn()===$before,'Failed transfer leaves no record');
    $pdo->exec("UPDATE accounts SET status='inactive' WHERE id=$b");
    rejected(fn()=>$post(['account_id'=>$b]));
    $pdo->exec("UPDATE accounts SET status='active',current_balance=9999999999999.99 WHERE id=$b");
    rejected(fn()=>$post(['type'=>'transfer','amount'=>'1','destination_account_id'=>$b]));
    check($balance($a)==='25000.00','Overflow rollback source balance');
    $pdo->exec("UPDATE accounts SET current_balance=1000 WHERE id=$b");
    $expense = new App\Services\ExpenseService();
    $category = (int)$pdo->query('SELECT id FROM categories LIMIT 1')->fetchColumn();
    $expense->create(['account_id'=>$a,'category_id'=>$category,'amount'=>'12000','expense_date'=>date('Y-m-d'),'description'=>'Purchase','status'=>'posted'],$actor);
    rejected(fn()=>$service->reverse($income,'Already spent',$actor));
    check($balance($a)==='13000.00','Failed reversal atomicity');
    $history=(new App\Repositories\TransactionRepository())->history($actor,$a,1);
    check($history['total']===4,'History includes expense and transaction rows');
    check(count(array_filter($history['rows'],fn($r)=>(int)$r['user_id']!==$uid))===0,'History ownership');
    check((new App\Repositories\TransactionRepository())->history($actor,$foreign,1)['total']===0,'Foreign account history hidden');
    try { $repo->delete($b,$uid); throw new RuntimeException('Used account deleted'); } catch (PDOException $e) { check($e->errorInfo[1]===1451,'Transaction account protected'); }
    check((int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE entity='account_transactions'")->fetchColumn()===5,'Post and reversal auditing');

    $transactionRepo = new App\Repositories\TransactionRepository();
    check($transactionRepo->history($actor,$a,1,['type'=>'income'])['total']===1,'Income filter');
    check($transactionRepo->history($actor,$a,1,['status'=>'reversed'])['total']===2,'Reversed filter');
    check($transactionRepo->history($actor,$a,1,['type'=>'expense','status'=>'posted','q'=>'Purchase','from'=>date('Y-m-d'),'to'=>date('Y-m-d')])['total']===1,'Combined inclusive filters');
    check($transactionRepo->history($actor,$a,1,['q'=>'Wrong destination'])['total']===1,'Reversal reason search');
    check($transactionRepo->history($actor,$a,1,['q'=>'%'])['total']===0,'Search wildcard treated literally');
    check($transactionRepo->history($actor,$foreign,1,['type'=>'income'])['total']===0,'Filtered ownership');

    $super = (new App\Repositories\UserRepository())->findWithRole(1);
    check(in_array('transactions.create_all',$super['permissions'],true),'Super Admin posting permission seeded');
    check(!in_array('transactions.create_all',$actor['permissions'],true),'User cannot post across users');
    check(!in_array('transactions.reverse_all',$actor['permissions'],true),'User cannot reverse across users');
    $adminPost = array_replace($base,['amount'=>'20','submission_token'=>bin2hex(random_bytes(32))]);
    $adminId = $service->post($adminPost,$super);
    $detail = $transactionRepo->findVisible($adminId,$actor);
    check((int)$detail['user_id']===$uid,'Admin transaction belongs to account owner');
    check($detail['activity'][0]['actor_name']===$super['name'],'Posting audit identifies administrator');
    check($balance($a)==='13020.00','Admin posts to user account');
    $service->reverse($adminId,'Administrator correction',$super);
    check($balance($a)==='13000.00','Admin reverses user account');
    check($transactionRepo->findVisible($adminId,$actor)['activity'][1]['actor_name']===$super['name'],'Reversal audit identifies administrator');
    rejected(fn()=>$service->reverse($adminId,'Again',$super));
    rejected(fn()=>$service->post(array_replace($adminPost,['submission_token'=>bin2hex(random_bytes(32)),'type'=>'transfer','destination_account_id'=>$foreign]),$super));
    $foreignId = $service->post(array_replace($adminPost,['account_id'=>$foreign,'submission_token'=>bin2hex(random_bytes(32))]),$super);
    check($transactionRepo->findVisible($foreignId,$actor)===null,'Private details hidden');
    rejected(fn()=>$service->reverse($foreignId,'Not mine',$actor));
    $readOnly = ['id'=>$uid,'permissions'=>['transactions.view','finance.view_all','transactions.reverse']];
    check($transactionRepo->findVisible($foreignId,$readOnly)!==null,'Finance viewer sees details');
    rejected(fn()=>$service->reverse($foreignId,'Read access only',$readOnly));
    $adminAdjustment=$service->post(array_replace($adminPost,['type'=>'adjustment','submission_token'=>bin2hex(random_bytes(32))]),$super);
    $withoutAdjust=$super; $withoutAdjust['permissions']=array_values(array_diff($super['permissions'],['transactions.adjust']));
    rejected(fn()=>$service->reverse($adminAdjustment,'Missing adjustment permission',$withoutAdjust));
    $service->reverse($adminAdjustment,'Restore test balance',$super);

    // Exercise the real router, session, permission middleware and form rendering.
    $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash('TransactionTest123!',PASSWORD_DEFAULT),$uid]);
    $server=proc_open([PHP_BINARY,'-S','127.0.0.1:18766','-t','public','tests/crud-router.php'], [['pipe','r'],['file','NUL','a'],['file','NUL','a']],$pipes,dirname(__DIR__),null,['bypass_shell'=>true,'create_new_console'=>false]);
    if (!is_resource($server)) throw new RuntimeException('Cannot start test server.');
    $curl=curl_init();
    curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>'',CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>5]);
    $request=static function(string $path, ?array $form=null) use ($curl): array {
        curl_setopt($curl,CURLOPT_URL,'http://127.0.0.1:18766'.$path);
        curl_setopt($curl,CURLOPT_POST,$form!==null);
        if ($form!==null) curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($form));
        $body=curl_exec($curl);
        return [(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE),$body];
    };
    try {
        for($i=0;$i<30;$i++){ [$status,$body]=$request('/login'); if($status===200)break; usleep(100000); }
        check($status===200,'Login available');
        preg_match('/name="_csrf" value="([^"]+)"/',$body,$match);
        $csrf=$match[1];
        check($request('/transactions')[0]===302,'Anonymous access redirects');
        check($request('/login',['_csrf'=>$csrf,'email'=>'user@expenzo.com','password'=>'TransactionTest123!'])[0]===302,'Login succeeds');
        [$status,$body]=$request('/transactions/create?account_id='.$a);
        check($status===200 && str_contains($body,'Post Transaction'),'Form renders');
        preg_match('/name="_csrf" value="([^"]+)"/',$body,$match); $csrf=$match[1];
        preg_match('/name="submission_token" value="([^"]+)"/',$body,$match); $submission=$match[1];
        $form=array_replace($base,['_csrf'=>$csrf,'submission_token'=>$submission,'amount'=>'10']);
        check($request('/transactions',array_replace($form,['_csrf'=>'invalid']))[0]===419,'CSRF enforced');
        [$status,$body]=$request('/transactions',array_replace($form,['amount'=>'-2']));
        check($status===422 && str_contains($body,'amount-error'),'Inline amount error');
        check($request('/transactions',$form)[0]===302,'HTTP posting');
        check($request('/transactions',$form)[0]===302,'HTTP duplicate submission');
        check($balance($a)==='13010.00','HTTP posting changes balance once');
        [$status,$body]=$request('/transactions?account_id='.$a);
        check($status===200 && str_contains($body,'Transaction history'),'History renders');
        check($request('/transactions?from=2026-02-30')[0]===422,'Invalid filter date rejected');
        check($request('/transactions?from=2026-02-02&to=2026-02-01')[0]===422,'Reversed date range rejected');
        check($request('/transactions?type=invalid')[0]===422,'Invalid type rejected');
        [$status,$filteredBody]=$request('/transactions?type=income&status=posted&q=Salary');
        check($status===200 && str_contains($filteredBody,'name="from"') && str_contains($filteredBody,'Reset'),'Filter UI and combined HTTP query');

        [$status,$body]=$request('/transactions/'.$income);
        check($status===200 && str_contains($body,'expense-overview') && str_contains($body,'Reason for reversal'),'Details match expense layout with reversal form');
        check($request('/transactions/'.$foreignId)[0]===404,'Foreign details hidden over HTTP');
        check($request('/transactions/'.$foreignId.'/reverse',['_csrf'=>$csrf,'reason'=>'Foreign'])[0]===404,'Foreign reversal blocked over HTTP');
        check($request('/transactions/'.$income.'/reverse',['_csrf'=>'bad','reason'=>'Bad token'])[0]===419,'Reversal CSRF check');
        check($request('/transactions/'.$income.'/reverse',['_csrf'=>$csrf,'reason'=>' '])[0]===422,'Inline reversal reason validation');
        $ownReversible=$service->post(array_replace($base,['amount'=>'1','submission_token'=>bin2hex(random_bytes(32))]),$actor);
        check($request('/transactions/'.$ownReversible.'/reverse',['_csrf'=>$csrf,'reason'=>'Own correction'])[0]===302,'Own reversal succeeds');
        check($request('/transactions/'.$ownReversible.'/reverse',['_csrf'=>$csrf,'reason'=>'Again'])[0]===422,'Repeated reversal blocked');
        [$status,$body]=$request('/transactions/'.$ownReversible);
        check($status===200 && str_contains($body,'Own correction') && !str_contains($body,'id="reverse-transaction"'),'Reversed details show reason without action');

        $pdo->exec("DELETE rp FROM role_permissions rp JOIN permissions p ON p.id=rp.permission_id JOIN user_roles ur ON ur.role_id=rp.role_id WHERE ur.user_id=$uid AND p.name LIKE 'transactions.%'");
        check($request('/transactions')[0]===403,'View permission enforced');
        check($request('/transactions/create')[0]===403,'Create permission enforced');
        check($request('/transactions',$form)[0]===403,'Post permission enforced');
        check($request('/transactions/'.$income.'/reverse',['_csrf'=>$csrf,'reason'=>'Denied'])[0]===403,'Reverse permission enforced');
        $request('/logout');
        $pdo->prepare('UPDATE users SET password_hash=? WHERE id=1')->execute([password_hash('TransactionTest123!',PASSWORD_DEFAULT)]);
        [$status,$body]=$request('/login');
        preg_match('/name="_csrf" value="([^"]+)"/',$body,$match);
        check($request('/login',['_csrf'=>$match[1],'email'=>'admin@expenzo.com','password'=>'TransactionTest123!'])[0]===302,'Admin login');
        [$status,$body]=$request('/transactions/create?account_id='.$a);
        check($status===200 && str_contains($body,'data-subtitle="Demo User"') && str_contains($body,'Test bank'),'Admin account choices include owner labels');
        preg_match('/name="_csrf" value="([^"]+)"/',$body,$match); $adminCsrf=$match[1];
        preg_match('/name="submission_token" value="([^"]+)"/',$body,$match);
        check($request('/transactions',array_replace($base,['amount'=>'2','_csrf'=>$adminCsrf,'submission_token'=>$match[1]]))[0]===302,'Admin HTTP posting into user account');
        $newId=(int)$pdo->query('SELECT MAX(id) FROM account_transactions')->fetchColumn();
        check($request('/transactions/'.$newId)[0]===200,'Admin sees details');
        check($request('/transactions/'.$newId.'/reverse',['_csrf'=>$adminCsrf,'reason'=>'Admin HTTP correction'])[0]===302,'Admin HTTP reversal');
        check($balance($a)==='13010.00','Admin HTTP reversal restores balance');

    } finally { curl_close($curl); proc_terminate($server); proc_close($server); }
    echo "Passed $checks transaction integration and HTTP checks.\n";
} finally {
    $admin->exec("DROP DATABASE IF EXISTS `$name`");
}