<?
$by=20;

$page_id=(int)$sub0; $start=$page_id*$by;

extract($trad_filters = sess::retrieve('trad_filters'));

    $items_list = locale::get_projects_items($trad_filters['project_id']);
    $mask_search = $trad_filters['strict_search']?"%s ILIKE '%%%s%%'":"%s LIKE '%s'";

if($action=="item_save") try {
        //un petite galere ici, les nom des clées sont base64_encodé
    $done=0; $items_vals = $_POST['items_vals']; if(!$items_vals) $items_vals = array();
    $items_keys = array_combine(array_map('base64_decode',  $k = array_keys($items_vals) ),$k);
    $items_vals = array_combine(array_keys($items_keys), $items_vals);
    $items_filtered = array_intersect_key($items_vals, array_flip($items_list));
    foreach($items_filtered as $item_key=>$trads){  $encoded_key = $items_keys[$item_key];
      foreach($trads as $lang_key=>$value){
        if(!($value = specialchars_decode($value))) continue; $done++; 
        sql::replace("ks_locale_values", compact('value'), compact('item_key','lang_key'));
        jsx::js_eval("\$N('items_vals[{$encoded_key}][{$lang_key}]').fireEvent('reset').highlight()");
      }
    }if(!$done) throw rbx::ok("Aucune modification n'a été détectée");
}catch(rbx $e){}



$verif_items=array(
    'lang_key'=>$lang_keys,
    'item_key'=>$items_list,
);


$verif_untrad=array(
    "true"
    //"trad_us.value !=''", //uniquement si l'anglais est traduit
);

if( $trad_filters['translated_item'] && !$trad_filters['untranslated_item'] ) // traduits && !(pas_traduits)
    $verif_untrad[] = "trad.value !='' ";
if( !$trad_filters['translated_item'] && $trad_filters['untranslated_item'] ) // !(traduits) && pas_traduits
    $verif_untrad[] = "COALESCE(trad.value,'')='' ";
if( !$trad_filters['translated_item'] && !$trad_filters['untranslated_item'] ) // !(traduits) && !(pas_traduits)
    $verif_untrad[] ="trad.value IS NULL ";

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

if($trad_filters['item_trad'])
    $verif_untrad[] = sprintf($mask_search,'trad.value',$trad_filters['item_trad']);


$query_items = "SELECT item_key, lang_key
    FROM `ks_languages`,`ks_locale_items_list`
    ".sql::where($verif_items)
;  //sql::query($query_items);print_r(sql::brute_fetch());print_r(sql::$queries);die;
$query="
    SELECT
        category_items.item_key,
        category_items.lang_key as lang_key,
        trad.value as value,
        trad_us.value as value_us
    FROM ($query_items) as category_items
    LEFT JOIN `ks_locale_values` AS trad USING(item_key, lang_key)
    LEFT JOIN `ks_locale_values` AS trad_us ON trad_us.lang_key='$lang_root'
        AND trad_us.item_key = category_items.item_key
    ".sql::where($verif_untrad)." 
    ORDER BY category_items.item_key ASC, trad.lang_key
";

sql::query($query); $max=sql::rows();
$items_list=sql::brute_fetch(false,false,$start,$by);


    //get metas info about currents item_keys
$item_keys = array_extract($items_list, "item_key", true);


//shop disponible ?
sql::query("SELECT `ks_locale_items_list`.*,
              (sshot.item_key  IS NOT NULL) as item_sshot  
            FROM `ks_locale_items_list` 
            LEFT JOIN (
              SELECT item_key
              FROM
              `ks_locale_tag_items` 
              WHERE (item_x IS NOT NULL) 
              GROUP BY item_key
            ) AS sshot USING(item_key)       
    ".sql::where(array('item_key'=>$item_keys))
);

$items_infos=sql::brute_fetch("item_key");
//print_r(sql::$queries);die;


$pages_str = dsp::pages($max,$by,$page_id,"/?$href_fold/items_list//");
