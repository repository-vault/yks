<?
  $product_id = (string)$sub1;
  $action = (string) $argv0;

  if($product_id) $product = products::instanciate($product_id);

  if($action == "search") {
    $result = array();
    $where = "product_name ILIKE '%{$_POST['search']}%'
              OR product_ref ILIKE '%{$_POST['search']}%'";
    $product_list = products::from_where($where);

    foreach ($product_list as $product) {
      $result[] = array($product->product_id, "[{$product->product_id}] {$product->product_ref} - {$product->product_name}");
    }

    echo json_encode($result);
    die; // AJAX !
  }