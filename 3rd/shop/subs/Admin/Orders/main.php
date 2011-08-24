<?

$sess_flag = "yks_list.ks_shop_orders";
$current_liste = sess::retrieve($sess_flag);


tpls::export(array('orders_list'=> "$href_fold/list"));



if(!$current_liste) {

    $filters = array(
        'filter_context'=> array(
            'distributor_id'=> $distributors_list,
            //'user_id'=> users::get_children($user_visibility_roots),
            'order_value!=0'
        ),
    );

    $current_liste = new yks_list( "ks_shop_orders",  $filters);
    $current_liste->href  = "/?$href_fold/list";
    $current_liste->target = "order_main_box";
        //initial sort
    $current_liste->order_by("order_end","DESC");


    sess::store($sess_flag, $current_liste); 
    //print_r(unserialize(serialize($current_liste)));
}
