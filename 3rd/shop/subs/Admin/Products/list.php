<?

if(!$user_id) $user_id = null;



if($action == "product_delete") try {
    $product_id = (int)$_POST['product_id'];
    $verif_product = compact('product_id');

    $res = sql::delete("ks_shop_products_list", $verif_product);
    if(!$res)
        throw rbx::error("Impossible de proceder à la suppression de ce produit");
    rbx::ok("Ce produit a été supprimé");
    jsx::$rbx = false;

}catch(rbx $e){}


$verif_products = array(
    'user_id'=>$user_id,
);
/*
    "(parent_id IS NULL
        OR parent_id = product_id
        OR ( parent_id != product_id AND product_relation_type='derivation') 
    )"
*/

    //products_tree
sql::select("ks_shop_products_list", $verif_products, "product_id");
$products_list = sql::fetch_all();


$products_infos = new products_list($products_list);
$products_infos = $products_infos->get_products_definition();


