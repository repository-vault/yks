<?
    //only jsx, thx

$field_name = $sub0;
$user_id = (int) $sub1;

$user_path = "";
if($user_id) $user_path = users::print_path($user_id);
tpls::export(compact('user_id', 'user_path'));



if(!$field_name)
    return rbx::error("Invalid field name");

tpls::export(compact('field_name'));

if($action=="check_names")try{
    $users_list=explode("&gt;",$_POST['users_txt']);
    $user_id=USERS_ROOT; $users_checked = array();
    foreach($users_list as $user_name){
        $children_list=users::get_children($user_id,1); //direct child
        $user_name = sql::clean(trim($user_name));
        $where = array("user_name LIKE '$user_name%'");
        $order='char_length(user_name)';
        $cols=array('user_name');

        $tmp = users::get_infos($children_list, $cols, $where, $order, 0, 1);
        if( !($user_id = (int)key($tmp)) ){$users_checked[]=''; break;}
        $users_checked[$user_id]=$tmp[$user_id]['user_name'];
    }
    
    $vals = join(' > ',$users_checked);
    jsx::js_eval("\$('$field_name').set('value', $user_id);");
    jsx::js_eval("this.set('value', '$vals' ).focus();");

}catch(rbx $e){}

