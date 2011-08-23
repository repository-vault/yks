<?

$meta_product_id = (int)$sub0;
$meta_product = $meta_products_list[$meta_product_id];

if($action == "edit_meta_product") try {

  $data = array (
    'meta_product_name' => (string)$_POST['meta_product_name'],
  );
  if($meta_product)
    $meta_product->sql_update($data);
  else
    products_meta::create($data);


  jsx::js_eval(JSX_PARENT_RELOAD);
  jsx::js_eval(JSX_CLOSE);
} catch(rbx $e) {}