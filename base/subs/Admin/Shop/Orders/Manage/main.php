<?



$order_id = (int) $sub0;

$order = new order($order_id);

if(!$order->order_id)
    return rbx::error("Invalid order_id #$order_id");


    //formatte les données pour une validation en sortie (date..)
$shop_orders_fields = fields(yks::$get->tables_xml->ks_shop_orders);

$order_dsp = $order->format_output();



