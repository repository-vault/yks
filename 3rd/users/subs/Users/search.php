<?

if($action=="user_filter")try {
    $q=$_POST['user_name']; $pattern  = $q;
    $mask = bool($_POST['search_strict'])?"LIKE '%s'":"LIKE '%%%s%%'";
    if($pattern) $pattern= sprintf($mask,$pattern);

    $data=array();
    if(is_numeric($q)) {
        $data['user_id'] = $q;
    }elseif($pattern){
	$data['user_name'] = array('sql'=>$pattern);
    }
    $data = array_filter($data);
    error_log(print_r($data,1));
    $user_filter['depth']=(bool)$_POST['search_deep'];

    $user_filter['where']=$data;
    jsx::js_eval("Jsx.open('/?$href_fold/list//{$user_filter['user_id']}','users_list',this)");

}catch(rbx $e){}