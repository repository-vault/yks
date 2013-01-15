<?

$tmp_path="cache/trad_export/".md5(time());

function from_categ_translate($categ_list,$lang_key,$lang_fallback){
	global $query_locale_value;
	$verif_categories=array('category_name'=>$categ_list);
	$verif_categories=sql::row("trad_categories_list",$verif_categories,"category_id");
	sql::select("ks_category_item",$verif_categories,"item_key");
	$verif_items=array(
		'item_key'=>sql::brute_fetch(false,'item_key'),
		'lang_key'=>$lang_key
	);if(!$categ_list) unset($verif_items['item_key']);

	$query=sprintf($query_locale_value,sql::where($verif_items),$lang_fallback);
	sql::query($query); $data=array(); $flip=array("\n"=>"\\n","\r"=>'',"<CRLF>"=>"\\n");
	while($l=sql::fetch())
		$data[$l['item_key']] = strtr(trim($l['value']),$flip);
	return $data;
}


if($action=="generate_lang") try {
	$project_id=$_POST['project_id'];
	$project_name=$projects_dsp[$project_id]['project_name'];

	$lang_key=$_POST['lang_key'];
	$lang_fallback=array_get(sql::row("ivs_languages",compact('lang_key')),'lang_fallback');
	$locale_path="$tmp_path/$lang_key";

	files::create_dir($locale_path);$files_list=array();



		//file 1
	$files_list[]=$locale_file="$locale_path/tres-$lang_key.txt";$contents='';

	$categories_list=array(
			'1. Soft Activisu', 
                        '1. Soft Bitmap Category Activisu',
                        '1. Soft Common Activisu &amp; Swing',
 	);
	$items_translation=from_categ_translate($categories_list,$lang_key,$lang_fallback);
	foreach($items_translation as $item_key=>$value) {
		$head= (substr($item_key,0,9) == "IVSRESFP_")?'RESFP':'RES';
		$contents.="$head $item_key\nTEXT=$value\n";
	} 
	$categories_list='1. Soft ActivisuFootPage';
	$items_translation=from_categ_translate($categories_list,$lang_key,$lang_fallback);
	foreach($items_translation as $item_key=>$value)  $contents.="RESFP $item_key\nTEXT=$value\n";
	file_put_contents($locale_file,$contents);

	
		//file 2
	$files_list[]=$locale_file="$locale_path/PrintTrad-$lang_key.trd";$contents='';
	$items_translation=from_categ_translate(false,$lang_key,$lang_fallback);
	foreach($items_translation as $item_key=>$value) $contents.="$item_key=$value\n";
	file_put_contents($locale_file,$contents);


		//file 3
	$files_list[]=$locale_file="$locale_path/Measure-$lang_key.txt";$contents='';
	$categories_list='1. Stereo Measures';
	$items_translation=from_categ_translate($categories_list,$lang_key,$lang_fallback);
	foreach($items_translation as $item_key=>$value) $contents.=substr($item_key,4)."=$value\n";
	file_put_contents($locale_file,$contents);



	$archive_file="$locale_path/$lang_key.zip";
	$archive = new PclZip($archive_file);
	foreach($files_list as $file)
		@$archive->add($file,PCLZIP_OPT_REMOVE_ALL_PATH);

	///jsx::js_eval("Urls.reloc('$archive_file')");
	rbx::ok("Trouvez <a href='$archive_file'>ici</a> votre fichier");


}catch(rbx $e){}