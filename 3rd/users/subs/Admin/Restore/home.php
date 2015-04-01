<?php

//Pagination
$page_id = (int)$sub0;
$by = 50;
$start = $by * $page_id;

$deletions_list = array();
sql::select("zks_deletion_history");
$deletions_list = array_sort_deep(sql::brute_fetch("zks_deletion_id"), 'deletion_time', 'arsort');

$max = count($deletions_list);
$deletions_list = array_reindex(array_slice($deletions_list, $start, $by), 'zks_deletion_id');

$pages_list  = dsp::pages($max, $by, $page_id, "/?$href//");

if($action == 'deletion_restore') try {
    sql::$queries = array();
    $deletion_id = (int)$_POST['deletion_id'];
    if(!$deletions_list[$deletion_id ])
        throw rbx::error("Invalid deletion reference");

    _sql_base::raw_restore($deletion_id );
    rbx::ok("Successfully restored deletion : #$deletion_id");

    jsx::js_eval(jsx::RELOAD);
} catch(Exception $e){ rbx::error("Could not restore deletion $deletion_id"); }

