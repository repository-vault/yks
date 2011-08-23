<?

if($action == "meta_product_trash") try {
  
  $meta_product_id = $_POST['meta_product_id'];
  $meta_product = $meta_products_list[$meta_product_id];
  if(!$meta_product)
    throw rbx::error("Vous n'avez pas le droit de détruire ce meta produit");
  
  $res = $meta_product->sql_delete();
  if(!$res)
      throw rbx::error("Impossible de détruire ce meta produit");
  
}catch(rbx $e) {}