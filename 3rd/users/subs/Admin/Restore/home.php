<?

$deletions_list = array();
sql::select("zks_deletion_history");
$deletions_list = sql::brute_fetch("zks_deletion_id");


if($action == 'deletion_restore') try {
    sql::$queries = array();
    $zks_deletion_id = (int)$_POST['deletion_id'];
    $verif_deletion = compact('zks_deletion_id');
    $deletion_blob = sql::value("zks_deletion_history", $verif_deletion, 'deletion_blob');
    $restore_blob = json_decode($deletion_blob, true);


    $token = sql::begin();
    foreach($restore_blob as $table_name => $restore_data)
        foreach($restore_data as $line)
            sql::insert($table_name, $line);

    sql::delete("zks_deletion_history", $verif_deletion);

    sql::commit($token);

    rbx::ok("Successfully restored deletion : #$zks_deletion_id");


} catch(rbx $e){}