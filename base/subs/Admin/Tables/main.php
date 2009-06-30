<?

$query_id = (int)$sub0;
$verif_query = compact('query_id');
$query_infos = sql::row("ks_queries_list", $verif_query);

sql::select("ks_queries_params", $verif_query);
$query_infos['params'] = sql::brute_fetch('query_param_key');

