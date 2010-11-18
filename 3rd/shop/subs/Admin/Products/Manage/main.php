<?
$product_id = (int)$sub0;
if($product_id){
    $verif_product = compact('product_id');
    $product_infos = sql::row("ks_shop_products_list", $verif_product);
    tpls::export($product_infos);
}




    //retrieve roots products informations

$verif_roots = "parent_id=product_id OR parent_id IS NULL
    OR (parent_id!=product_id && product_relation_type='derivation')
";
$res = sql::select("ks_shop_products_list", $verif_roots, "product_id");

$products_infos = new products_list(sql::fetch_all());
$products_splat = $products_infos->get_products_definition();


//$products_splat = sql::brute_fetch("product_id");
$tmp = array_reindex($products_splat, array("user_id", "product_id"));

$products_roots_tree = (array)$tmp[null];;

foreach($products_users as $user_id=>$user_infos) {
    $products_roots_tree["user_$user_id"]= array(
        'disabled'=>'disabled',
        'depth'=>$user_infos['depth'],
        'product_name' => $user_infos['user_name']
    );
    foreach((array) $tmp[$user_id] as $tmp_id=>$tmp_infos)
        $products_roots_tree[$tmp_id] = array_merge(  //merge depth
            array('depth'=>$user_infos['depth']), 
            $tmp_infos);
}

