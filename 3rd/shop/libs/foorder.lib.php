<?

class foorder extends order {



  protected $order_key;
  public $basket; //basket 
  public $shop; // shop
  
    //check if we have a valid session_id for the current order, if not, create one
  function __construct($order_key, $order_extras = array()){

    $this->order_key = $order_key;

    $this->order_id = (int)sess::$sess->retrieve($this->order_key);
    $verif_order = array('order_id'=>$this->order_id, 'order_status'=>array('process','config'));

    $this->order_id = sql::row(self::sql_table, $verif_order)
        ? $this->order_id
        : $this->renew($order_extras);

    sess::$sess->store($this->order_key, $this->order_id);

    parent::__construct($this->order_id);

    $basket_key = $this->order_key."_{$this->order_id}_basketss";

    $this->basket = &sess::$sess->retrieve($basket_key); // yeah
    if((!$this->basket)) $this->basket = new basket();

    $this->basket->order = $this;

  }
  

  
    //cancel order
  private function renew($order_extras=array()){

    $data=array('order_status'=>'aborted', 'order_end'=>_NOW);
    if($this->order_id)
        sql::update("ks_shop_orders", $data, $this);//self::basket_clean(); not mandatory

    $data = array(
        'user_id'      => sess::$sess['user_id'],
        'order_start'  => _NOW,
        'order_status' => 'process',
    ); $data = array_merge($data, $order_extras);

    $order_id = sql::insert("ks_shop_orders", $data, true);
    if($order_extras['order_id'])
        $order_id = $order_extras['order_id']; //h4<k

    return $order_id;
  }
  
  function precalc($products_list) {

         // returns compact('order_infos', 'products_list');
    $tmp = orders_manager::precalc($this, $products_list);

    extract($tmp); 
    // APPLICATION DES REGLES DE GESTION (dégression, personnalisation)
    $this->shop->apply_precalc_hooks($this, $order_infos, $products_list);
    return compact('order_infos', 'products_list');
  }
  


/**
* On grave l'order dans la bdd, on verrouille, order_id n'existe plus \o/
*/
  function close($basket, $extras=array()){ 
    $basket->close();

    extract($this->calc()); //retrieve 'products_list','order_infos'

    $this->shop->apply_checkout_hooks($this, $order_infos, $products_list);

    $data=array(
        'addr_billing'   => $this->addrs['billing']['addr_id'],
        'addr_delivery'  => $this->addrs['delivery']['addr_id'],
        'order_value'    => $order_infos['total_ci'],
        'shipping_value' => $order_infos['shipping_total'],
        'taxes_value'    => $order_infos['total_taxes'],
        'order_status'   => 'paying',
    ); extract($data);

    if(!($order_value && $addr_billing && $addr_delivery )) {
        error_log("Error during shop order. Order value : '$order_value' ; Addr billing '$addr_billing' ; Add delivery : '$addr_delivery'");
        return false;
    }


    $verif_order = array('order_id' => $this->order_id);

    sql::update('ks_shop_orders', $data, $verif_order);
    sess::$sess->delete($this->order_key);
    return $this->order_id;
  }




}