<?

class locale {

  const sql_table = "ks_locale_languages";
  const sql_key = "lang_key";

  public static $sql_table = locale::sql_table;
  public static $sql_key = locale::sql_key;

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

  static function get_iso_lang($lang_key) {
    return ''; // todo
  }

  static function export($lang_key, $verif_tags) {

    $lang_infos = sql::row(self::$sql_table, compact('lang_key'));
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

    sql::select(self::$sql_table, "true", "lang_key");
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


  static function lang_update($lang_list) {
    $value = array('update_date' => _NOW);
    $lang_and_fallback = self::lang_and_fallback($lang_list);
    foreach($lang_and_fallback as $lang)
      sql::replace('ks_languages_last_update',$value,array('lang_key'=>$lang));
  }

  // On modifie toutes les langues qui ont besoin de nous
  // càd : toutes celles qui fallback sur nous, et leurs parents, etc
  static function lang_and_fallback($lang_list) {
    if(!$lang_list)
      return array();

    $langs = array();
    foreach($lang_list as $lang) {
      $where = array('lang_fallback'=>$lang);
      sql::select(self::$sql_table, $where, "lang_key");
      $langs_res = sql::fetch_all();
      $langs = array_merge($langs,$langs_res);
    }

    $langs = array_diff($langs, $lang_list);
    $fallback_langs = self::lang_and_fallback($langs);
    return array_merge($lang_list, $fallback_langs);
  }

  /**
  * Return information for a lang (iso alpha 2 and 3 of the country and the lang, name...)
  * 
  * @param array $locale_languages_key lang_key
  */
  public static function languages_infos($locale_languages_key) {
    $where = array('lang_key' => $locale_languages_key);
    sql::query("SELECT
      country_name,
      lang_code,
      iso_639_languages.alpha_3 AS lang_alpha3,
      iso_3166_countries.alpha_3 AS country_alpha3,
      iso_639_languages.alpha_2 AS lang_alpha2,
      iso_3166_countries.alpha_2 AS country_alpha2  
      FROM ivs_locale_languages
      JOIN iso_3166_countries USING (country_code)
      JOIN iso_639_languages USING (lang_code)
      ".sql::where($where)."
      GROUP BY country_name,
      lang_code,
      iso_639_languages.alpha_3,
      iso_3166_countries.alpha_3,
      iso_639_languages.alpha_2,
    iso_3166_countries.alpha_2;");

    $lang_infos = sql::brute_fetch();
    return $lang_infos;
  }

}
