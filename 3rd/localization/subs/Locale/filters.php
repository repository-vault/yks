<?

$export        = (bool) $sub0;
$user_tmp_dir  = users::get_tmp_path(sess::$sess->user_id);


if($export) {
    $lang_key   = $sub1;
    $export_key = "export_trd.$lang_key";
    $tmp_file   = sess::retrieve($export_key);
    if(!is_file($tmp_file)) die ("File unavaible");

    $lang_key  = explode('-', $lang_key);
    $file_name  = $lang_key[0].'-'.strtoupper($lang_key[1]).".trd";
    files::download($tmp_file, $file_name);
}



if($action=="download_trd")try {
    $verif_lang = array(
        'lang_key'=> $lang_key = $_POST['lang_key'],
    );
    if(!$lang_key)
        throw rbx::error("Invalid language");

    sql::select("ks_localize_view", $verif_lang, "item_key, value");
    $trd_contents = sql::brute_fetch("item_key", "value");

    $trd_contents = array_map('clean_feeeds', $trd_contents);
    ksort($trd_contents);

    $export_key = "export_trd.$lang_key";

    files::create_dir($user_tmp_dir);
    $tmp_file = tempnam($user_tmp_dir, "trd");
    sess::store($export_key, $tmp_file);

    $trd_contents = mask_join(CRLF, $trd_contents, '%2$s=%1$s');  
    
    file_put_contents($tmp_file, $trd_contents);



    rbx::ok("<a class='ext' href='/?$href//1;$lang_key'>click here to download</a>");
    //jsx::js_eval("window.open('/?$href//1;$lang_key', '_blank');");

}catch(rbx $e){}

if($action=="apply_filters") try {
    
    if(!in_array($project_id=(int)$_POST['project_id'], $locale_projects))
        throw rbx::error("Veuillez specifier un projet");

    $project_id = array_merge(array($project_id),
            sql_func::get_children($project_id,"ks_projects_list","project_id"));

    if(!$lang_keys = array_intersect($locale_languages,(array)$_POST['lang_keys']))
        throw rbx::error("Veuillez specifier au moins une langue");

    $trad_filters = array(
        'project_id'            => $project_id,
        'lang_keys'             => array_values($lang_keys),
        'untranslated_item'     => (bool)$_POST['untranslated_item'],
        'translated_item'       => (bool)$_POST['translated_item'],
        'item_key'              => $_POST['item_key'],
        'item_trad'             => $_POST['item_trad'],
        'strict_search'         => (bool)$_POST['strict_search'],
    );

    sess::store('trad_filters',$trad_filters );
    //throw rbx::error(print_r($trad_filters,1));

    
    return jsx::js_eval("Jsx.open('/?$href_fold/items_list','items',this)");

}catch(rbx $e){}