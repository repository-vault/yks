<?php
  $page_id = (int)$sub0;
  $export = (bool)$sub1;

  exyks::$head->title = "Liste des produits";
  $products_list = exyks_session::fetch('shop_products_list');

  $by = 50;
  $start = $page_id * $by;
  $max = count($products_list);

  $products_list = array_slice((array)$products_list , $start, $by, true);
  $pages = dsp::pages($max, $by, $page_id, "/?$href//");

  if($export) {
    exyks_renderer_excel::$creator = sess::$sess['user_name'];
    exyks_renderer_excel::process();
  }

  if(!$user_id) $user_id = null;

  if($action == "product_delete") try {
    $product_id    = (int) $_POST['product_id'];
    $verif_product = compact('product_id');

    $res = sql::delete("ks_shop_products_list", $verif_product);
    if(!$res)
      throw rbx::error("Impossible de proceder à la suppression de ce produit");
    rbx::ok("Ce produit a été supprimé");
    jsx::$rbx = false;
  } catch(rbx $e) {
  }
