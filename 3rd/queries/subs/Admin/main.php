<?

$query_lib = exyks_paths::resolve("path://yks/3rd/queries/libs");

    classes::extend_include_path($query_lib);
    classes::register_class_path("query_param", "$query_lib/query_param.lib.php");

tpls::page_def("list");
tpls::export(array('queries_fold'=>$subs_fold));



$query_id = (int)$sub0;
if($query_id) try {

    $query = new query($query_id);

} catch(Exception $e){}

