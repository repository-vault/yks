<?

class basket {

  static function clean($clean_db = false){
    $order_id=(int)sess::$sess['order_id']; $verif_order=compact('order_id');
    if($clean_db) sql::delete("ks_shop_orders_parts", $verif_order); //?
    orders::$basket = array('products_list'=>array());
  }

  /*
    Fermeture du panier, on insere tt le contenu de la session dans la table order_parts
    et on detruit le pannier
  */

  static function close(){
    $order_id=(int)sess::$sess['order_id']; $verif_order=compact('order_id');
    $verif_update=array('order_id'=> $order_id, 'order_status' => array('process','config'));
    $data=array('order_status'=>'config'); //status : spécificité de la commander

    if(!sql::update("ks_shop_orders",$data,$verif_update)) return false;
    sql::delete("ks_shop_orders_parts",$verif_order);

    foreach(orders::$basket['products_list'] as $product_id=>$product_infos)
        sql::insert('ks_shop_orders_parts', array(
            'order_id'=>$order_id,
            'product_id'=>$product_id,
            'product_qty'=>$product_infos['product_qty'],
            //products_options
        ));

    self::clean(false);
    return true;
  }


  /*
    Retourne les informations sur la transaction
    Entrée : rien
    Retour  : list($order_infos, $products_list);
    - order_infos (informations sur l'etat de la boutique  et sur la commande avec ses informations complétées pour la transaction courante )
            total_ci ( total charge inclues ), total_ce (total, charge exclues), shipping_total et total_taxes
    - products_list ( liste des produits de la boutique avec les informations de prix complétées/qtée voulue )  ( basket['products_list']) 
  */

  static function precalc($products_list = false,$order_infos = false){
    if(!$products_list) $products_list = &orders::$basket['products_list'];
    if(!$order_infos) $order_infos = &orders::$order_infos;

    return orders::precalc($products_list, $order_infos);
  }



  static function  product_add($product_id, $product_options=array('product_qty'=>1)){
    $product_qty = abs((int)$product_options['product_qty']); unset($product_options['product_qty']);

    if(!$product_qty)
        throw rbx::error("Please specify a quantity");
    if(!$product_infos=orders::$products_list[$product_id])
        throw rbx::error("This product is unavaiable");

    if($product_infos['product_options']['unsellable'])
        throw rbx::error("You must specify options");

    if(!orders::$basket["products_list"][$product_id])
        orders::$basket["products_list"][$product_id]=$product_infos;
    orders::$basket["products_list"][$product_id]['product_qty']+=$product_qty;

  }

}
