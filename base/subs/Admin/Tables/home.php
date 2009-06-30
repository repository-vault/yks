<?

sql::select("ks_queries_list");
$queries_list = sql::brute_fetch('query_id');

sql::select("ks_queries_params",true,"query_id, query_id AS has_parameters", "GROUP BY query_id");
$queries_list = array_merge_numeric($queries_list,  sql::brute_fetch("query_id"));

