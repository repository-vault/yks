<?

if($action=="delete")try{
    $verif_delete = $_POST['uid'];
    sql::delete($table_name, $verif_delete);
    jsx::$rbx = false;
}catch(rbx $e){}



$max_rows = sql::value($table_name, $initial_criteria, "count(*)");
$by = 10;
$page_id = (int)$sub0;
$start = $page_id * $by;

    //on recherche les valeurs initiales et on indexe le tableaux sur rien
sql::select($table_name, $initial_criteria, "*", "LIMIT $by OFFSET $start");
$data = sql::brute_fetch();
//$data = call_user_func_array(array('sql', 'brute_fetch_depth'), $table_keys);



$pages_str = dsp::pages($max_rows, $by, $page_id, "/?$href//");

