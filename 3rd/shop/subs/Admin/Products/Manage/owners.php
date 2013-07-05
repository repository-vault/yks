<?

  // ***** For ajax search module *****
  $search = $sub0;
  if($search) {
    $users_list = $users_infos = users::get_children_infos(1, array("user_type != 'ks_users'"), array('user_name', 'user_mail', 'user_login',  'user_connect'));
    $ajax_search_items = array();
    foreach($users_list as $k=>$v)
      $ajax_search_items[] = array($k, "#{$v['user_id']} - {$v['user_name']}");
    echo json_encode($ajax_search_items);die; // AJAX !
  }

$owners_list = $product->owners_list;

//**************** actions ***************//

if( $action == "owner_add") try {
  $user_id = (int)$_POST['user_id'];
  if(!$user_id) throw rbx::error("Cet utilisateur n'est pas valide");

  $data = array (
    'product_id' => $product->product_id,
    'user_id'    => $user_id,
  );
  $res = sql::insert('ivs_shop_products_owners',$data);
  if(!$res) throw rbx::error("Erreur sql durant l'insertion");
  jsx::js_eval(jsx::RELOAD);

} catch(rbx $e) {}

if( $action == "owner_delete") try {
  $user_id = (int)$_POST['user_id'];
  if(!$user_id) throw rbx::error("Cet utilisateur n'est pas valide");

  $verif = array (
    'product_id' => $product->product_id,
    'user_id'    => $user_id,
  );
  $res = sql::delete('ivs_shop_products_owners',$verif);
  if(!$res) throw rbx::error("Impossible de supprimer cet enregistrement.");
  jsx::js_eval(jsx::RELOAD);

} catch(rbx $e) {}