<?



tpls::page_def("list");
tpls::export(array('queries_fold'=>$subs_fold));



$query_id = (int)$sub0;
if($query_id) try {

    $query = new query_db($query_id);

} catch(Exception $e){}

