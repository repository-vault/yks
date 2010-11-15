<?

if($action=="user_filter")try {
    $q=$_POST['user_name']; $pattern  = $q;
    $mask = bool($_POST['search_strict'])?"ILIKE '%s'":"ILIKE '%%%s%%'";
    if($pattern) $pattern = sprintf($mask, $pattern);

    if(!$q) {
        $user_filter = false;
    } else {
        $data = array();
        if(is_numeric($q)) {
            $data['user_id'] = $q;
        }elseif($pattern){
            $data[] = "( user_name $pattern OR user_mail $pattern)";
        }
        $data = array_filter($data);
        $user_filter['depth'] = (bool)$_POST['search_deep'];
        $user_filter['where'] = $data;
    }

    jsx::js_eval("Jsx.open('/?$href_fold//{$user_filter['user_id']}/list','users_list',this)");

}catch(rbx $e){}