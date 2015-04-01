<?php


if(!$query)
    abort(405);
$export = (bool) $sub0;


$params_values = sess::retrieve('query_params');

$query->prepare($params_values);
exyks::$head->title = $query['query_name'];



try {
    if($query->ready)
        $query->execute();
} catch(Exception $e){
    rbx::error("L'appel de la requete a echoué ".specialchars_encode($query->sql_query));
    return;
}

if($export){
    exyks_renderer_excel::$creator = sess::$sess['user_name'];
    exyks_renderer_excel::process();
}