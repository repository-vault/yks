<?

include "taxes_desactivate.php";
include "shop.php";



class products {
    /**
        retourne les informations sur une liste de produit	
        get_infos ($product_id || $products_id || $product_infos || $products_infos )
    */

  static function get_infos($product_infos){
    if(!$product_infos) return array();

    $verif_status=array('product_status'=>'active');

    if($product_start_id=(int)$product_infos['product_id'])
        $products_infos=array($product_start_id=>$product_infos);
    $first = reset($product_infos);

    if(!$item['product_id']){ // si $product_infos['product_id'] existe deja.. rien à faire
        $products_infos = self::get_tree_definition($product_infos);
        //if(!$force) $verif_products+=$verif_status;
    }

    $types=array();$products_children=array();

    foreach($products_infos as $product_id=>&$product_infos){
        $product_infos = &$products_infos[$product_id]; //php bug (5.2.1)
        $parent_id = $product_infos['parent_id'];

        //..$types[]=$type=$product_infos['product_type'];
        //recursive load of pack product_type

        $product_infos['product_img'] = "/imgs/".SITE_CODE."/products/$product_id";
        if($parent_id && $parent_id != $product_id) {
            if(!glob(ROOT_PATH.$product_infos['product_img']))
                $product_infos['product_img']=&$products_infos[$parent_id]['product_img'];
            if(!$product_infos['product_price'] )
                $product_infos['product_price']=&$products_infos[$parent_id]['product_price'];

        }

        continue; //!
        if($type!="pack")continue;

        sql::query("SELECT * FROM `ks_ecommerce_packs_products`
            WHERE pack_id='$_product_id'
            ORDER BY product_order ASC
        ");$subs_list=sql::brute_fetch('product_id');
        $subs_infos=products::get_infos(array_keys($subs_list),$site);
        $subs_list=array_intersect_key($subs_list,$subs_infos);
        $products_children[$product_id]['children']=array_merge_numeric($subs_list,$subs_infos);

    } unset($product_infos);
    $products_infos=array_merge_numeric($products_infos,$products_children);

    $verif_products=array('product_id'=>array_keys($products_infos));
    sql::select("ks_shop_products_categories",$verif_products);
    while(extract(sql::fetch()))
        $products_infos[$product_id]['product_categs'][]=$category_id;

        //suivant ce qui avait été demandé initialement, on retourne un, ou plusieurs trucs
    return $product_start_id?$products_infos[$product_start_id]:$products_infos;
  }


    /*
        retourne les information d'un produit, ou d'une liste d'id de produits 
    */
  static function get_infos_splat($product_id){
    $products_list=(array)$product_id;
    $infos = self::get_infos($products_list);
    return (array)(is_array($product_id) ? $infos : $infos[$product_id]);
  }



 static function get_ci($infos,$addr,$unit=false){
    $qty=$unit?1:$infos['product_qty'];
    return round($infos['product_price']*$qty*(1+taxes::get($infos['tax_type'],$addr)),2);
 }


 static function get_tree_definition($products_list){
    if(!$products_list) return array();
    $verif_products = sql::where(array('product_id'=>$products_list));
    sql::select("ks_shop_products_list",$verif_products,"*", "ORDER BY product_id=parent_id DESC"); 
    $liste = sql::brute_fetch("product_id"); $parents = array();
    foreach($liste as $product_infos)$parents_list[]=$product_infos['parent_id'];
    $parents_list = array_filter(array_diff($parents_list, $products_list));
    
    return array_merge_numeric(self::get_tree_definition( $parents_list), $liste);
}

 static function get_children($product_id,$depth=-1){
    return get_children($product_id,'ks_shop_products_list','product_id',$depth);
 }

 static function get($tables, $type, $addr){

    $table_priority=array(
        ''=>'all',
        'geo_zone'=>'',
        'country_code'=>$addr['country_code'],
        'region_id'=>'',
        'addr_code'=>$addr['addr_code'],
        'addr_city'=>$addr['addr_city'],
    );$price=0;
    foreach ($table_priority as $zone_type=>$zone_geo){
        if (isset($tables[$type][$zone_type][$zone_geo]))
          $price=$tables[$type][$zone_type][$zone_geo];
    } return $price;
 }
}


