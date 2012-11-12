<?

$meta_products_list = products_meta::from_where(sql::true);
$meta_products_list = array_sort_deep($meta_products_list, 'meta_product_name');

$product_meta_products = pick($product->meta_products_list, array());

//************** actions **************
if( $action == "meta_product_add") try {
  $meta_product_id = (int)$_POST['meta_product_id'];
  if(!$meta_product_id) throw rbx::error("Ce meta produit n'est pas valide");

  $data = array (
    'product_id'      => $product->product_id,
    'meta_product_id' => $meta_product_id,
  );
  $res = sql::insert('ivs_shop_meta_products_products',$data);
  if(!$res) throw rbx::error("Erreur sql durant l'insertion");
  jsx::js_eval(jsx::RELOAD);

} catch(rbx $e) {}


if( $action == "meta_delete") try {
  $meta_product_id = (int)$_POST['meta_product_id'];
  if(!$meta_product_id) throw rbx::error("Ce meta produit n'est pas valide");

  $data = array (
    'product_id'      => $product->product_id,
    'meta_product_id' => $meta_product_id,
  );
  $res = sql::delete('ivs_shop_meta_products_products',$data);
  if(!$res) throw rbx::error("Erreur sql durant la suppression");
  jsx::js_eval(jsx::RELOAD);

} catch(rbx $e) {}