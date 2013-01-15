<?

if($item)
  $value_en_us = $item->get_trad_value($lang_root);

if($action=="item_manage") try {

  $item_key=($item?$item['item_key']:$_POST['item_key']);
  $data=array(
    'item_key'      => $item_key,
    'item_comment'  => rte_clean($_POST['item_comment'])
  );
  $tags_list  = array_filter((array)$_POST['tag_id']);
  if(!$tags_list)
    throw rbx::warn("Veuillez choisir au moins un tag","tag_id");

  $value_us   = rte_clean($_POST['value_us']);
  $verif_item = compact('item_key');

  if(!$value_us)
    throw rbx::error("Veuillez saisir un texte pour la version $lang_root");
  if(!$item_key)
    throw rbx::warn("Veuillez choisir un identifiant","item_key");


  if(!$item) {
    // -- Insertion --
    if(sql::row("ks_locale_items_list",$verif_item))
      throw rbx::error("L'item $item_key est deja utilisé");
    sql::insert("ks_locale_items_list",$data);
    $mode_str = "inséré";

  } else {
    // -- Update --
    $item->sql_update($data);
    sql::delete("ks_locale_tag_items", $verif_item);
    sql::delete("ks_locale_values",array('item_key'=>$item_key, 'lang_key'=>$lang_root));
    $mode_str = "modifié";
  }

  // Liste des tags
  foreach($tags_list as $tag_id)
    sql::insert("ks_locale_tag_items",compact('tag_id','item_key'));

  // Traduction par défaut (en-us)
  $data=array(
    'item_key'  => $item_key,
    'value'     => $value_us,
    'lang_key'  => $lang_root
  );sql::insert("ks_locale_values",$data);

  // Mise à jour de la table de date de generation
  sql::replace('ks_languages_last_update', array('update_date' => _NOW), array('lang_key'=>$lang_root));  

  rbx::ok("Item <b>$item_key</b> $mode_str");


} catch(rbx $e){}

