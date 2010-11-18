<?

if($action=="order_manage")try {
    //les seuls champs inputés sont : order_end, order_status, order_value
    $only_fields = array('order_end', 'order_status', 'order_value', 'order_comment');
    $shop_orders_fields_tmp = array_intersect_key($shop_orders_fields, array_flip($only_fields));
    $data = mykses::validate($_POST, $shop_orders_fields_tmp);

    $res = $order->update($data, bool($_POST['update_profile']));
    if($res['infos']) rbx::ok($res['infos']);

    rbx::ok("Modifications enregistrées");

}catch(rbx $e){ rbx::error("Impossible de modifier la commande"); }


$distributor_infos = users::get_infos_unique($order->distributor_id);
$distributor_id = $distributor_infos['user_id'];


