<?php

class shop {
  public $shop_key; // READONLY (mais pas private sinon on peut pas le consulter)
  private $products; //products_list
  private $checkout_hook; //callback
  private $precalc_hook; //callback

  public static function init(){
    $__DIR__ = dirname(__FILE__);
    classes::register_class_paths(array(
        'products_list' => $__DIR__."/products_list.lib.php",
    ));
  }

  function register_checkout_hook($callback){    $this->checkout_hook = $callback;  }
  function register_precalc_hook($callback){     $this->precalc_hook = $callback;  }
  
  function __construct($shop_key, $verif_products) {
    $this->shop_key = $shop_key;
    $this->load_products_list($verif_products);
  }

  function apply_precalc_hooks(order $order, &$order_infos, &$products_list){

    if(!$this->precalc_hook)
      return;
    $success = call_user_func_array($this->precalc_hook, array($order, &$order_infos, &$products_list));
    if(!$success)
      throw rbx::error("Invalid content in your basket");
  }

  function apply_checkout_hooks(order $order, &$order_infos, &$products_list){
    if(!$this->checkout_hook)
      return;

    $success = call_user_func_array($this->checkout_hook, array( $order, &$order_infos, &$products_list));
    if(!$success)
      throw new Exception("Checkout aborted");
  }

  function __get($key){
    if(method_exists($this, $getter="get_$key"))
        return $this->$getter();
  }
 
  static function currency_format($value){
    return sprintf('%s', $value);
  }

  static function get_categories_list(){
    static $categories_list = false; //$
    if($categories_list) return $categories_list;
    sql::select("ks_shop_categories_list");
    return $categories_list = sql::brute_fetch('category_id');
  }

  private function load_products_list($verif_products) {
    sql::select("ks_shop_products_list", $verif_products, "product_id");
    $products_list = sql::fetch_all();
    $this->products = new products_list($products_list);

  }

  protected function get_head_products_list(){
    return $this->head_products_list = $this->products->get_head_products_list();
  }


  protected function get_products_list(){
    return $this->products_list = $this->products->get_products_list();
  }

  function get_order($verif_order){
    $order_key = $this->shop_key.'_order';

    $order = new foorder($order_key, $verif_order);
    $order->shop   = $this;

    return $order;
  }

  function get_displayed_products($verif_products) {
    if(is_string($verif_products))
        $verif_products = array('product_categs'=>$verif_products);

    $displayed_products_list = array_filter_criteria($this->head_products_list, $verif_products);
    $displayed_products_list = array_sort_deep($displayed_products_list, "product_order");
    return $displayed_products_list;
  }
}

class categories {
  static function get_products($categories_list){
    $categories_list = (array) $categories_list;

   $verif_shop = array( "category_id" => $categories_list);
   sql::select("ks_shop_products_categories",$verif_shop, "product_id");
   $products_list = sql::brute_fetch(false,"product_id"); 


    return products::get_children($products_list);
  }

}

