<?






if($action=="item_manage") try {
	
	$data=array(
		'item_key'=>$item_key=($item_exists?$item_exists['item_key']:$_POST['item_key']),
		'item_comment'=>rte_clean($_POST['item_comment'])
	);
	$tags_list=(array)$_POST['tag_id'];
	$value_us=rte_clean($_POST['value_us']);
	$verif_item=compact('item_key');

	if(!$value_us)
		throw rbx::error("Veuillez saisir un texte pour la version $lang_root");
	if(!$item_key)
		throw rbx::warn("Veuillez choisir un identifiant","item_key");

	if(!$tags_list)
		throw rbx::warn("Veuillez choisir au moins un tag","tag_id");

	if(!$item_exists){
		if(sql::row("ks_locale_items_list",$verif_item))
			throw rbx::error("L'item $item_key est deja utilisé");
		sql::insert("ks_locale_items_list",$data);

	}else {
		sql::delete("ks_locale_tag_items", $verif_item);
		sql::delete("ks_locale_value",array('item_key'=>$item_key, 'lang_key'=>$lang_root));
	}

	foreach($tags_list as $tag_id)
		sql::insert("ks_locale_tag_items",compact('tag_id','item_key'));



	$data=array(
		'item_key'=>$item_key,
		'value'=>$value_us,
		'lang_key'=>$lang_root
	);sql::insert("ks_locale_values",$data);

	rbx::ok("Item <b>$item_key</b> inséré");


} catch(rbx $e){}

