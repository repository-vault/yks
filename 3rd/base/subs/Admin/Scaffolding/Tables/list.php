<?php

if($action=="delete")try{
    $verif_delete = $_POST['uid'];
    sql::delete($table_name, $verif_delete);
    jsx::$rbx = false;
}catch(rbx $e){}



if($action == "update") try {

    $data = mykses::validate($_POST, $table_xml);
    $data = array_diff_key($data, $initial_criteria);
    sql::update($table_name, $data, $initial_criteria);
    
    rbx::ok("Modification enregistrées");
} catch(rbx $e){}




$max_rows = sql::value($table_name, $initial_criteria, "count(*)");
$by = 10;
$page_id = (int)$sub0;
$start = $page_id * $by;



    //on recherche les valeurs initiales et on indexe le tableaux sur rien
sql::select($table_name, $initial_criteria, "*", "LIMIT $by OFFSET $start");

//$data = call_user_func_array(array('sql', 'brute_fetch_depth'), $table_keys);

if($mode=="vertical") {
    $data = sql::brute_fetch();
} else {
    $data = sql::fetch();

    $key_name  = key($table_keys);
    $key_value = $data[$key_name];
    $verif_data = array($key_name=>$key_value);

}




$pages_str = dsp::pages($max_rows, $by, $page_id, "/?$href//");

