<?

$product_id = (int)$sub0;
$clone = ((string)$sub1 == 'clone');

if($product_id){
    $verif_product = compact('product_id');
    $product = products::instanciate($product_id);
    tpls::export((array)$product['data']);
}

if($clone) {
  sql::begin();
  try {
  $cloned_product = $product->clone_product_and_variations();
  } catch(rbx $e) {
    sql::rollback();
    throw $e;
  }
  sql::commit();
  reloc("?/$href_fold//".$cloned_product->product_id);
}

// Retrieve roots products informations
$verif_roots = array(
    "parent_id IS NULL",
);
sql::$queries = null;
sql::select("ks_shop_products_list", $verif_roots, "product_id");
$res = sql::fetch_all();

$products_roots_tree = products::from_ids($res,false);
