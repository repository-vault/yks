<?

//order_status : aborted,process,config(closing process)


class orders_manager extends _sql_base {

  public static function  close_products(order $order) {

    foreach($order->products_list as $product_id=>$product_infos){
        $profile_key = $product_infos['product_options']['profile_key'];
        if(!$profile_key) continue; 
        list($table_name, $field_name, $profile_key)
            = preg_list('#^(.*?)\[(.*?)\]\[(.*?)\]$#', $profile_key);
        $key_name = reset(fields(yks::$get->tables_xml->$table_name,"primary"));
        $verif_criteria  = array($key_name =>$order->$field_name);
        $data = array($profile_key=>array('sql'=>"`$profile_key` + {$product_infos['product_qty']}"));
        sql::update($table_name, $data, $verif_criteria);
    }
  }


  function get_products_list(order $order){

    sql::select("ks_shop_orders_parts", $order);
    $products_list   = sql::brute_fetch('product_id');
    $basket          = new products_list(array_keys($products_list));
    $products_infos  = $basket->get_head_products_list();

    return $order->products_list = array_merge_numeric($products_list, $products_infos);
  }
  

  public static function calc(order $order){

    $verif_order = array('order_id' => $order->order_id);

         //gestion complexe sur les options ici
    sql::select("ks_shop_orders_parts", $verif_order);
    $products_list = sql::brute_fetch('product_id');

    $products_infos = products::get_infos_splat(array_keys($products_list));
        //completion de product_qty & options
    $products_list  = array_merge_numeric($products_list, $products_infos);

    return $order->precalc($products_list);
  }

    //$products_list = array($product_id=>&$product_infos )
  public static function precalc(order $order, $products_list) {

    $order_infos = array();

    $order_infos['order_id'] = $order->order_id;
    $order_infos['order_addrs'] = $order->addrs;
    $order_infos['total_ci'] = 0;
    $order_infos['total_ce'] = 0;
    $order_infos['delivery_time'] = null; // Null par défaut, mis à jour dans le hook

    foreach($products_list as $product_id=>&$product_infos){
      $product_infos['shipping_cost'] = shipping::get($product_infos['shipping_type'], $order->addrs['delivery'])*$product_infos['product_qty'];
      $product_infos['tax_rate'] = taxes::get($product_infos['tax_type'], $order->addrs['addr_billing']);
      $product_infos['price_ce'] = $product_infos['product_qty']*$product_infos['product_price'];
      $product_infos['price_ci'] = $product_infos['price_ce']*(1+$product_infos['tax_rate']);

      $order_infos['delivery_time'] = max($order_infos['delivery_time'], $product_infos['product_delivery_delay']);

      if($product_infos['product_qty']){
        $order_infos['total_ci']+=$product_infos['price_ci']+$product_infos['shipping_cost'];
        $order_infos['total_ce']+=$product_infos['price_ce'];
        $order_infos['shipping_total']+=$product_infos['shipping_cost'];
      }
    }

    if($order->deposit_infos) {
        $order_infos['deposit_infos'] = $order->deposit_infos;
        if($order_infos['total_ci'] > $order->deposit_infos['deposit_down_limit']) {
            $rate = $order->deposit_infos['deposit_rate'];
            $order_infos['deposit_amount'] = $order_infos['total_ci']*$rate;
        }
    }


    $order_infos['total_taxes'] = $order_infos['total_ci'] - $order_infos['total_ce'];

    
    return compact('order_infos', 'products_list');
  }



}
