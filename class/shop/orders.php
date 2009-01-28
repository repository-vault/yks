<?

//order_status : aborted,process,config(closing process)
include "basket.php";

class orders {

  static $basket = array();
  static $order_infos = array();
  static $products_list = array(); //shortcut like "avaiable products"

    //check if we have a valid session_id for the current order, if not, create one
  static function verif($order_extras=array()){
    $order_id= (int)sess::$sess['order_id'];
    $verif_order = array('order_id'=>$order_id,'order_status'=>array('process','config'));
    return sql::row("ks_shop_orders",$verif_order) ? $order_id : self::renew($order_extras,$order_id);
  }

  static function init($order_infos){
        self::$order_infos = $order_infos;
        self::$products_list = & self::$order_infos['products_list'];
        self::$basket = &sess::$sess['basket'];
        if((!self::$basket)) self::$basket = array('products_list'=>array());
  }

  static function addr_set($addr_type, $addr_infos){
    self::$order_infos['order_addrs'][$addr_type] = $addr_infos;
  }

    //cancel order
  static function renew($order_extras=array(),$order_id=false){
    if(!$order_id)$order_id=(int)sess::$sess['order_id'];
    $verif_order=compact('order_id');$data=array('order_status'=>'aborted','order_end'=>_NOW);
    sql::update("ks_shop_orders",$data,$verif_order);//self::basket_clean(); not mandatory
    $data=array_merge(array(
        'user_id'=>sess::$sess['user_id'],
        'order_start'=>_NOW,
        'order_status'=>'process',
    ),$order_extras);
    sess::$sess['order_id'] = $order_id = sql::insert("ks_shop_orders",$data,true);
    return $order_id;
  }

  static function calc(){

    $order_infos=self::$order_infos;
    $order_id = $order_infos['order_id'];

    $verif_order=compact('order_id');

         //gestion complexe sur les options ici
    sql::select("ks_shop_orders_parts", $verif_order);
    $products_list = sql::brute_fetch('product_id');

    $products_infos=products::get_infos_splat(array_keys($products_list));
        //completion de product_qty & options
    $products_list=array_merge_numeric($products_list,$products_infos);

    return self::precalc($products_list, $order_infos);
  }

    //$products_list = array($product_id=>&$product_infos )
  static function precalc($products_list, $order_infos) {
    $order_addrs        = $order_infos['order_addrs'];
    $deposit_infos      = $order_infos['deposit_infos'];
    $order_infos['total_ci'] = 0;
    $order_infos['total_ce'] = 0;
    $order_infos['delivery_time'] = 86400*30; // temps de livraison 30j

    foreach($products_list as $product_id=>&$product_infos){
      $product_infos['shipping_cost']=shipping::get($product_infos['shipping_type'],$order_addrs['addr_delivery'])*$product_infos['product_qty'];
      $product_infos['tax_rate']=taxes::get($product_infos['tax_type'],$trans_addrs['addr_billing']);
      $product_infos['price_ce']=$product_infos['product_qty']*$product_infos['product_price'];
      $product_infos['price_ci']=$product_infos['price_ce']*(1+$product_infos['tax_rate']);

      if( in_array($product_infos['parent_id'], array(30,34)) ) $order_infos['delivery_time'] = 86400*30*2; //!

      if($product_infos['product_qty']){
        $order_infos['total_ci']+=$product_infos['price_ci']+$product_infos['shipping_cost'];
        $order_infos['total_ce']+=$product_infos['price_ce'];
        $order_infos['shipping_total']+=$product_infos['shipping_cost'];
      }
    }
    if($deposit_infos) {
        if($order_infos['total_ci'] > $deposit_infos['deposit_down_limit']) {
            $rate = $deposit_infos['deposit_rate'];
            $order_infos['deposit_amount'] = $order_infos['total_ci']*$rate;
        }
    }

    $order_infos['total_taxes']=$order_infos['total_ci']-$order_infos['total_ce'];

    return compact('order_infos', 'products_list');
  }



    /**
        On grave l'order dans la bdd, on verrouille, order_id n'existe plus \o/
    */
  static function close($extras=array()){ 
    extract(orders::calc()); //retrieve 'products_list','order_infos'

    $order_addrs = $order_infos['order_addrs'];

    $data=array(
        'addr_billing'=>$order_addrs['billing']['addr_id'],
        'addr_delivery'=>$order_addrs['delivery']['addr_id'],

        'order_value'=>$order_infos['total_ci'],
        'shipping_value'=>$order_infos['shipping_total'],
        'taxes_value'=>$order_infos['total_taxes'],
        'order_status'=>'paying',
    );extract($data);

    if(!($order_value && $addr_billing && $addr_delivery )) return false;

    $order_id=$order_infos['order_id'];
    $verif_order=compact('order_id');

    sql::update('ks_shop_orders', $data, $verif_order);
    unset(sess::$sess['order_id']);
    return $order_id;
  }




}