<?php

class basket {
  private $products_list;


  function __construct(){
    $this->products_list = array();
  }

  function __sleep(){
    return array('products_list');
  }

  function __get($key){
    if(property_exists($this, $key)) //& is_scalar ?
        return $this->$key;
  }

  function clean($clean_db = false){
    $verif_order = array('order_id'=>$this->order->order_id);
    if($clean_db) sql::delete("ks_shop_orders_parts", $verif_order); //?
    $this->products_list = array();
  }

  /*
   * Fermeture du panier, on insere tt le contenu de la session dans la table order_parts
   * et on detruit le pannier
   */
  function close(){
    $order_id = $this->order->order_id; $verif_order = compact('order_id');

    $verif_update = array('order_id'=> $order_id, 'order_status' => array('process','config'));
    $data=array('order_status'=>'config'); //status : spécificité de la commander

    if(!sql::update("ks_shop_orders", $data, $verif_update))
        return false;

    sql::delete("ks_shop_orders_parts", $verif_order);

    foreach($this->products_list as $product_id=>$product_infos)
        sql::insert('ks_shop_orders_parts', array(
            'order_id'=>$order_id,
            'product_id'=>$product_id,
            'product_qty'=>$product_infos['product_qty'],
            //products_options
        ));

    $this->clean(false);
    return true;
  }


  /**
    Retourne les informations sur la transaction
    Entrée : rien
    Retour  : list($order_infos, $products_list);
    - order_infos (informations sur l'etat de la boutique  et sur la commande avec ses informations complétées pour la transaction courante )
            total_ci ( total charge inclues ), total_ce (total, charge exclues), shipping_total et total_taxes
    - products_list ( liste des produits de la boutique avec les informations de prix complétées/qtée voulue )  ( basket['products_list']) 
  */
  function precalc(){
    return $this->order->precalc($this->products_list);
  }


  function product_remove($product_id) {
    unset($this->products_list[$product_id]);
  }

  function  product_add($product_id, $product_options=array('product_qty'=>1)){

    $product_qty = abs((int)$product_options['product_qty']);
    unset($product_options['product_qty']);

    if(!$product_qty)
        throw rbx::error("Please specify a quantity");

    $product = $this->order->shop->retrieve_product($product_id);

    if(!$product)
        throw rbx::error("This product is unavailable");

    if($product['products_specifications']['unsellable'])
        throw rbx::error("You must specify options");

    if(!$this->products_list[$product_id])
        $this->products_list[$product_id] = clone $product;

    $this->products_list[$product_id]->product_qty += $product_qty;


  }

}
