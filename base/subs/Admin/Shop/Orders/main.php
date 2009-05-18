<?

$sess_flag = "yks_list.ks_shop_orders";
$current_liste = sess::retrieve($sess_flag);

include_once CLASS_PATH."/shop/orders.php";
include_once CLASS_PATH."/shop/products_list.php";

if(!$current_liste) {
 
    $user_head = array(3072);
    if(auth::verif('yks','admin')) $user_head = USERS_ROOT;
    $users_list = users::get_children($user_head);
    $filters = array(
        'filter_context'=> array('user_id'=>$users_list,'order_value!=0'),
    );

    $current_liste = new yks_list( "ks_shop_orders",  $filters);
    $current_liste->href  = "/?$href_fold/list";
    $current_liste->target = "order_main_box";
        //initial sort
    $current_liste->order_by("order_end","DESC");


    sess::store($sess_flag, $current_liste); 
    //print_r(unserialize(serialize($current_liste)));
}
