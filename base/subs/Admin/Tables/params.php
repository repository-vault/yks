<?


$params_str = array();
foreach($query_infos['params'] as $param_name=>$param_infos){
    $tmp = $param_infos['query_param_callback'];
    $callback = json_decode($tmp)?json_decode($tmp):$tmp;
    if(!is_callable($callback))continue; //error handling here

    $params_str [] = call_user_func($callback);

}

if($action=="query_params_set") try {
    $params_values = array();
    foreach($query_infos['params'] as $param_name=>$param_infos)
        $params_values[$param_name]= $_POST[$param_name];
    
    sess::store('query_params', $params_values);
    jsx::js_eval(JSX_WALKER);
    jsx::js_eval("Jsx.open('/?$href_base/data', 'query_data', this)");

}catch(rbx $e){}


