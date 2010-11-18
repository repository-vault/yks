<?

$page_id = (int) $sub0;
$current_liste->page_set($page_id);
$orders_list = $current_liste->get_infos();






$clients_list = array();
foreach($orders_list as $order_infos) $clients_list[]= $order_infos['user_id'];

$clients_list = users::get_infos($clients_list);
 