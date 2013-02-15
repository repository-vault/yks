<?

class locale {

  const sql_table          = "ks_locale_languages";
  const sql_table_localize = "ks_localize_view";
  const sql_key            = "lang_key";

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

  public static function get_projects($project_name, $strict=false) {

    // On cherche l'ID du projet dont on  a le nom :
    $where = array("project_name ilike '%$project_name%'"); 
    if($strict) $where = array("project_name ilike '$project_name'");
    sql::select("ks_projects_list", $where);
    $projects_ids = sql::brute_fetch("project_id");
    $locale_projects = array_keys($projects_ids); 

    // Enfants de notre(nos) projets
    $locale_projects = array_merge($locale_projects, sql_func::get_children($locale_projects,"ks_projects_list","project_id"));
    return $locale_projects;
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
    $infos = self::languages_infos($lang_key);
    return $infos[$lang_key]['lang_key_raw'];
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



  public static function get_languages_list($verif_languages = sql::true){

    sql::select(self::$sql_table, $verif_languages, "lang_key");
    $languages_list = sql::fetch_all();

    return $languages_list;

  }

  static function ini_escape($str){
    $flip = array("\n"=>"\\n","\r"=>'',"<CRLF>"=>"\\n");
    return strtr(trim($str),$flip);
  }

  static function format_init($hash){
    // Escape according to ini specs
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
  * Chain of fallbacking from a given lang
  * 
  * @param mixed $lang
  */
  static function fallback_list($lang_key) {

    static $languages_list  = false, $languages_metadata = false;

    if($languages_list === false) {
      sql::select(self::$sql_table);
      $languages_list = sql::brute_fetch('lang_key');
      $languages_metadata = self::languages_infos( array_keys($languages_list ));
    }

    $ret = self::fallback_node($languages_list, $lang_key);

    $ret[] = $languages_metadata[$lang_key]['lang_key_raw'];
    return $ret;
  }

  private static function fallback_node($languages_list, $lang_key, $chain_list=array()) {
    $lang_infos = $languages_list[$lang_key];
    $chain_list[] = $lang_key;
    // fin de chaine
    if(in_array($lang_infos['lang_fallback'],$chain_list))
      return $chain_list;
    // on creuse encore...
    return self::fallback_node($languages_list, $lang_infos['lang_fallback'], $chain_list);
  }




  static function fetch_locales_values_raw($lang_key){
      //poor man's inner join
    sql::select(self::sql_table_localize, array('lang_key' => $lang_key, 'value IS NOT NULL'));
    return sql::brute_fetch('item_key', 'value');
  }
  
  
  static function fetch_locales_values($lang_key, $filters = array()) {
    $fallback_chain = locale::fallback_list($lang_key);

    $verif = array(
      'lang_key' => $fallback_chain
    );
    $verif = array_merge($verif, $filters);

    sql::select(self::sql_table_localize, $verif);
    $values = sql::brute_fetch();

    $values_no_fallback = array();
    // On classe
    foreach($values as $value_infos)
      $values_no_fallback[$value_infos['item_key']][$value_infos['lang_key']] = $value_infos['value'];

    // On récupère la valeur avec gestion fallback
    $values_list = array();
    foreach($values_no_fallback as $item_key=>$items_trad) {
      foreach($fallback_chain as $fb_lang) {
        $values_list[$item_key] = $items_trad[$fb_lang]; // Une trad, on est content, on quitte la boucle        
        if(!isset($items_trad[$fb_lang]) || !$items_trad[$fb_lang])
          continue; // Pas de trad, on boucle
        break;
      }
    }

    ksort($values_list);
    return $values_list;
  }

  public static function get_locales_file($lang_key, $filters=array()) {
    $trd_contents = locale::fetch_locales_values($lang_key, $filters);
    return self::format_init($trd_contents);
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
      iso_3166_countries.alpha_2 AS country_alpha2,
      concat(iso_639_languages.alpha_2 , '-' ,iso_3166_countries.alpha_2) AS lang_key_raw,
      lang_key
      FROM `ks_locale_languages`
      JOIN iso_3166_countries USING (country_code)
      JOIN iso_639_languages USING (lang_code)
      ".sql::where($where)."
      GROUP BY country_name,
      lang_code,
      iso_639_languages.alpha_3,
      iso_3166_countries.alpha_3,
      iso_639_languages.alpha_2,
      iso_3166_countries.alpha_2,
      lang_key;");

    $lang_infos = sql::brute_fetch("lang_key");
    return $lang_infos;
  }

}
