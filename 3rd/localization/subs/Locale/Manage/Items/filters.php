<?

if($action=="apply_filters") try {

  $free_items = bool($_POST['free_items']);
  if(!in_array($project_id=(int)$_POST['project_id'], $locale_projects) && !$free_items)
    throw rbx::error("Veuillez specifier un projet");

  $trad_filters = array(
    'item_key'       => $_POST['item_key'],
    'strict_search'  => (bool)$_POST['strict_search'],
  );

  if(!$free_items) {
    $project_id = array_merge(array($project_id),
    sql_func::get_children($project_id,"ks_projects_list","project_id"));
    $trad_filters['project_id'] = $project_id;
    $items_list = locale::get_projects_items($trad_filters['project_id']);
  }
  else {
    $query = "
    SELECT item_key FROM
    ivs_locale_items_list
    WHERE item_key NOT IN (
      SELECT item_key
      FROM public.ivs_locale_tag_items
    )";
    sql::query($query);
    $items_list = sql::fetch_all();
  }

  $mask_search = $trad_filters['strict_search']?"%s ILIKE '%%%s%%'":"%s LIKE '%s'";

  $verif_items = array();

  $verif_items['item_key'] = $items_list;

  if($trad_filters['item_key']) {
    if (is_array($trad_filters['item_key'])) {
      $str = array();
      foreach($trad_filters['item_key'] as $item_key)
        $str[]= sprintf($mask_search,'item_key', $item_key);
      $verif_items[] = "(".join(' OR ', $str).")";
    }
    else {
      $verif_items[] = sprintf($mask_search,'item_key',$trad_filters['item_key']);
    }
  }
  $items_list = array_keys(locale_item::from_where($verif_items));


  sess::store("items_list_manage", $items_list);
  jsx::js_eval("Jsx.open('/?$href_fold/list', 'items_list', this)");

} catch(rbx $e){}