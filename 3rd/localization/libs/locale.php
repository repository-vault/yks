<?

class locale {

  static function init(){
    if(!classes::init_need(__CLASS__)) return;
    define('LOCALE_LIB_PATH', dirname(__FILE__));
    classes::register_class_paths(array(
        "locale_tag"           => LOCALE_LIB_PATH."/tag/tag.php",
        "locale_tag_manager"   => LOCALE_LIB_PATH."/tag/manager.php",
        "locale_item"          => LOCALE_LIB_PATH."/items.php",
        "locale_items_manager" => LOCALE_LIB_PATH."/items/manager.php",
    ));
  }

  static function get_projects_items($project_id){
    $verif_project = compact('project_id');
    sql::select('ks_locale_tags_list', $verif_project, "tag_id");
    $locale_tags_list = sql::fetch_all();

    $verif_tags = array('tag_id'=>$locale_tags_list);
    sql::select('ks_locale_tag_items', $verif_tags, "item_key");
    return sql::fetch_all();

  }

  static function export($lang_key, $verif_tags) {

    $lang_infos = sql::row("ks_languages", compact('lang_key'));
    if(!$lang_infos)
        throw rbx::error("Unknow language");

        //build lang lists
    $langs_list = array_unique(array($lang_key, $lang_infos['lang_fallback'], 'en-us'));


    sql::select("ks_locale_tags_list", $verif_tags, "tag_id");
    $tags_list = sql::fetch_all();

        //search for child tags
    $tags_list = array_unique(array_merge($tags_list, 
        sql_func::get_tree($tags_list , "ks_locale_tags_list", "tag_id", -1, "parent_tag")));

    $verif_tags = array('tag_id' => $tags_list);

    sql::select("ks_locale_tag_items", $verif_tags, "item_key");
    $items_list = sql::fetch_all();

    $verif_items = array(
        'lang_key' => $langs_list,
        'item_key' => $items_list,
    );
    $order = sprintf("ORDER BY FIND_IN_SET(lang_key, '%s') DESC", join(',', $langs_list));

    sql::select("ks_locale_values", $verif_items, "*", $order);
    $values = sql::brute_fetch("item_key", "value");
    return $values;
  }



  public static function get_languages_list(){

    sql::select("ks_languages", "true", "lang_key");
    $languages_list = sql::fetch_all();

    return $languages_list;

  }

  static function ini_escape($str){
    $flip = array("\n"=>"\\n","\r"=>'',"<CRLF>"=>"\\n");
    return strtr(trim($str),$flip);
  }

  static function format_init($hash){
        //escape according to ini specs
    $hash = array_map(array('self', 'ini_escape'), $hash);

    return mask_join(CRLF, $hash, '%2$s=%1$s');
  }




}
