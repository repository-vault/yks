<?

$page_id=(int)$sub0;
$by=20;
$start=$page_id*$by;


$depth=$user_filter['depth'] ?-1:1;
$children_list=users::get_children($user_id,$depth);


if($action=="user_remove") try {
    $verif_user=array('user_id'=>(int)$_POST['sub0']);
    sql::delete("ks_users_tree", $verif_user);
} catch(rbx $e){}


if($user_id==USERS_ROOT)
	$children_list=array_diff($children_list,array(USERS_ROOT));//!!this is a value, not a key


    //on trie les utilisateur par nom, et par type de users
$sort   = "user_type<>'{$user_infos['user_type']}' ,user_name";
$where  = $user_filter['where']?$user_filter['where']:array();
$cols   = array('user_name', 'user_type', 'auth_type');

$children_infos = users::get_infos($children_list, $cols, $where, $sort, $start, $by);



$max = $where?sql::$rows:count($children_list);

$pages=dsp::pages($max,$by,$page_id,"/?/Admin/Users//$user_id/list//","users_list",true);



sql::select("ks_users_addrs",$verif_user,"addr_id");
$addrs_list=sql::brute_fetch(false,'addr_id');
