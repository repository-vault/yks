<?

   if($action == "filter") try {

     $where = array(sql::true);

    // Categories
    if($_POST['category'])
      $products_ids = category::search_product_by_category(explode(',', $_POST['category']));

    // Products owners
    if($user_id = $_POST['product_owner'])
      $user_ids = array($user_id);
    if($user_id && $_POST['with_parent'])
      $user_ids = users::get_parents($user_id);
    if($user_ids) {
      $where_owner['user_id'] = $user_ids;
      sql::select("ivs_shop_products_owners", $where_owner, products::sql_key, "ORDER BY ".products::sql_key);
      $products_ids = $products_ids?array_intersect($products_ids,sql::fetch_all()):sql::fetch_all();
    }

    // Product name
    if($_POST['product_blob'])
      $where[] = sql::make_search_blob("product_name", $_POST['product_blob']);


    $where[products::sql_key]    = $products_ids;
    $where_children['parent_id'] = $products_ids;

    sql::select(products::sql_table, $where, products::sql_key, "ORDER BY ".products::sql_key);
    $products_ids = sql::fetch_all();
    sql::select(products::sql_table, $where_children, products::sql_key, "ORDER BY ".products::sql_key);
    $products_children_ids = sql::fetch_all();

    $products_list = products::from_ids(array_merge($products_ids, $products_children_ids));

    exyks_session::store("shop_products_list", $products_list);
    jsx::js_eval("Jsx.open('/?$href_fold/list','products_list', this)");
  } catch(rbx $e) {
  }