<?

if($action=="bench_sql") try {

    $data = array(
        'bench_iteration' => (int)$_POST['bench_iteration'],
        'query_sql'       => $_POST['query_sql'],
    );

    $data['bench_iteration'] = min(max($data['bench_iteration'], 10), 1000);


    $success = sql::qrow($data['query_sql']);
    if(!$success)
        throw rbx::error("Invalid query, aborting");

    $data['success'] = $success;

    $start = microtime(true);
    for($a=0;$a<=$data['bench_iteration'];$a++)
        sql::query($data['query_sql']);

    $end = microtime(true) - $start;

    rbx::ok("Itérations : {$data['bench_iteration']}"."<br/>");
    rbx::ok("Success : ".print_r($data['success'],1)."<br/>");
    rbx::ok("Temps : ".round($end*1000,2)."<br/>");
    rbx::ok("Loop :  ".round($end*1000 / $data['bench_iteration'],2)."<br/>");

} catch( rbx $e){}