<?


if($action=="query_params_set") try {
    $params_values = array();
    

    foreach($query->params_list as $param_uid=>$param)
        $params_values[$param_uid]=  $param->format_value($_POST[$param_uid]);


    sess::store('query_params', $params_values);
    jsx::js_eval(JSX_WALKER);
    jsx::js_eval("Jsx.open('/?$href_base/data', 'query_data', this)");

}catch(rbx $e){}


$params_str = '';
foreach($query->params_list as $param)
  $params_str .= $param->format_input();
