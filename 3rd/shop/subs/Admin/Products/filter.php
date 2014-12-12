<?

  if($action == "filter") try {
    if($user_id = $_POST['product_owner'])
      $user_ids = array($user_id);
    if($user_id && $_POST['with_parent'])
      $user_ids = users::get_parents($user_id);
    if($_POST['category'])
      $products_ids_from_category = category::search_product_by_category(explode(',', $_POST['category']));
    if($_POST['product'])
      $product_ids_from_filter = explode(',', $_POST['product']);

    if($user_ids) {
      $where_owner['user_id'] = $user_ids;

      if($product_ids_from_filter && $products_ids_from_category && $product_owner_ids = array_intersect($product_ids_from_filter, $products_ids_from_category))
        $where_owner[products::sql_key] = $product_owner_ids;
      elseif($product_ids_from_filter)
        $where_owner[products::sql_key] = $product_ids_from_filter;
      elseif($products_ids_from_category)
        $where_owner[products::sql_key] = $products_ids_from_category;
      elseif(!$where['user_id'])
        $where_owner = array(sql::true);

      sql::select("ivs_shop_products_owners", $where_owner, products::sql_key, "ORDER BY ".products::sql_key);
      $products_owner_ids = sql::fetch_all();
    }

    if($products_owner_ids) {
      $where          = array(products::sql_key => $products_owner_ids);
      $where_children = array('parent_id' => $products_owner_ids);
    } elseif($product_ids_from_filter && $products_ids_from_category && $product_ids = array_intersect($product_ids_from_filter, $products_ids_from_category)) {
      $where          = array(products::sql_key => $product_ids);
      $where_children = array('parent_id' => $product_ids);
    } elseif($product_ids_from_filter) {
      $where          = array(products::sql_key => $product_ids_from_filter);
      $where_children = array('parent_id' => $product_ids_from_filter);
    } elseif($products_ids_from_category) {
      $where          = array(products::sql_key => $products_ids_from_category);
      $where_children = array('parent_id' => $products_ids_from_category);
    } else $where = array(sql::true);

    if($where) {
      sql::select(products::sql_table, $where, products::sql_key, "ORDER BY ".products::sql_key);
      $products_ids = sql::fetch_all();
    }

    if($where_children) {
      sql::select(products::sql_table, $where_children, products::sql_key, "ORDER BY ".products::sql_key);
      $products_children_ids = sql::fetch_all();
    }

    if($products_ids && $products_children_ids)
      $products_list = products::from_ids(array_merge($products_ids, $products_children_ids));
    elseif($products_ids)
      $products_list = products::from_ids($products_ids);
    elseif($products_children_ids)
      $products_list = products::from_ids($products_children_ids);

    exyks_session::store("shop_products_list", $products_list);
    jsx::js_eval("Jsx.open('/?$href_fold/list','products_list', this)");
  } catch(rbx $e) {
  }