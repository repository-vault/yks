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

$user_ids = array($user_id);
if($with_parent)
  $user_ids = users::get_parents($user_id);

$verif_products = array(
    'user_id'=>$user_ids,
);

// Products tree
if(!$user_id)
  sql::select("ivs_shop_products_list", array(sql::true), "product_id", "ORDER BY product_id");
else
  sql::select("ivs_shop_products_owners", $verif_products, "product_id", "ORDER BY product_id");

$products_ids = sql::fetch_all();
$products_list = products::from_ids($products_ids);
//DebugBreak();

