<?
if($action == "query_trash") try {
    $query_id = (int)$_POST['query_id'];
    $query = new query($query_id);
    $query->trash();
    jsx::$rbx = false;
} catch(rbx $e){}


sql::select("ks_queries_list");
$queries_list = sql::brute_fetch('query_id');
