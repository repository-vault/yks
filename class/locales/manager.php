<?php



class locales_manager {
  const CONST_LOCALES_MASK = '#^(?!FLAG_).*(?<!_PATH|_MASK)$#';
  const sql_table_users_domains = 'as_users_profile_locale_domains';
  public static $module_locale_exists;
  const FINAL_FALLBACK = 'en-us';
  
  public static function init(){
    if(!classes::init_need(__CLASS__)) return;
    
    self::$module_locale_exists = class_exists('locale');

    $base = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    $current_hash = md5($base);

    if($_SESSION['langs']['current_hash'] != $current_hash) {
        $_SESSION['langs']['current_hash'] = $current_hash;
        $user_lang =  self::find_best_lang($base);
        $_SESSION['langs']['current'] = $user_lang;
    }
    $user_lang = $_SESSION['langs']['current'];
    exyks::store("USER_LANG", $user_lang);
  }

  /**
  * Retrieve languages list , whether from locale module if available or from static file config
  * @return array A list of every available language and their fallbacks
  */
  public static function get_languages_list(){

    static $languages_list = false;
    if($languages_list !== false)
      return $languages_list;

    $locales    = yks::$get->config->locales;
    $languages_list = array();

    //Whether the locale module exists and is loaded or not, must get a valid list of languages
    if(!self::$module_locale_exists ) {
      //Using locale conf file to forge languages list
      $languages  = preg_split(VAL_SPLITTER, $locales['keys'], -1, PREG_SPLIT_NO_EMPTY);
      //If the "keys" conf is not provided, forge a mock language list containing ultimate fallback
      if(!$languages)
        $languages = array(self::FINAL_FALLBACK);
        
      $languages_order  = array_filter(preg_split(VAL_SPLITTER, $locales['order']));
      $languages_order  = array_intersect($languages_order, $languages);

      foreach($languages as $lang_key) {
        $tmp_languages_order = $languages_order;

        do {
          $tmp_fback = array_shift($tmp_languages_order);
        }while($tmp_fback && $tmp_fback != $lang_key);

        if($tmp_fback == $lang_key) {
          //Fallbacks => suite du tableau d'ordre
          //!\ On a la langue "en cours" en tete du tableau et le fallback "de secours" à la fin
          array_unshift($tmp_languages_order, $tmp_fback);
          array_push($tmp_languages_order, self::FINAL_FALLBACK);
          $languages_list[$lang_key] = $tmp_languages_order;
        } else {
          //Fallbacks par défaut
          $languages_list[$lang_key] = array(
            $lang_key,
            self::FINAL_FALLBACK,
          );
        }
      }
    } else {
      //Using locale module
      foreach(locale::get_languages_list(array('used_for_website' => sql::true)) as $lang_key)
        $languages_list[$lang_key] = locale::fallback_list($lang_key);
    }
    return $languages_list;
  }
  
  public static function translate($str, $lang_key = false){
    if(!$lang_key) $lang_key = exyks::retrieve("USER_LANG");

    $entities = data::load("entities", $lang_key);
    if(!$entities) $entities = array();

    if(bool(yks::$get->config->locales['babelmode']))
      $entities = array_merge($entities, data::load("entities", "babel"));

    $entities = array_merge(
        array('&USER_LANG;' => substr($lang_key,0,5) ), //send iso 639 compatible locale 
        $entities,
        retrieve_constants(self::CONST_LOCALES_MASK, "&%s;")
    ); foreach(tpls::$entities as $k=>$v) $entities["&$k;"] = $v;
    if($entities){while($tmp!=$str){ $tmp=$str; $str=strtr($str,$entities);} $str=$tmp;}

    if(strpos($str,"&")!==false)$str = locales_processor::process_entities($str, $lang_key);

    if(preg_match(MASK_INVALID_ENTITIES, $str)) {
        syslog(LOG_INFO, "There are invalid entities in your document");
        $str = preg_replace(MASK_INVALID_ENTITIES,'&amp;',$str);
        if(preg_match("#<!\[CDATA\[(?s:.*?)\]\]>#",$str,$out)){
          $str= str_replace($out[0], str_replace("&amp;",'&',$out[0]),$str );
        }
    }

    return $str;
  }

  private static function correct_lang_weights($lang_weights){
    $lang_weights =  array_reverse($lang_weights);   $last_weight = 0;
    $lang_order = array();
    foreach($lang_weights as $lang_key=>&$lang_weight){
        if(!is_numeric($lang_weight)) $lang_weight = $last_weight;
        else $last_weight = $lang_weight;
        if(!$lang_order[$last_weight]) $lang_order[$last_weight] = array();
        array_unshift($lang_order[$last_weight], $lang_key);
    } unset($lang_weight); $lang_weights = array_reverse($lang_weights);

    krsort($lang_order);
    $lang_weights = array_keys($lang_order);

    $lang_final_order = array();
    for($a=0,$m=count($lang_weights);$a<$m;$a++) {
        $next_weight = (float) $lang_weights[$a+1];
        $current_weight = $lang_weights[$a];
        $current_elements = $lang_order[$current_weight];

        $ajustement_weight = ($current_weight  - $next_weight)/count($current_elements);
        foreach($current_elements as $k=>$lang_key)
            $lang_final_order[$lang_key] = (count($current_elements)-$k) * $ajustement_weight + $next_weight;
    }
    return $lang_final_order;
  }


    //retro-ponderation for no-country-specifics languages (e.g it,en;q=0.9,en-us;q=0.8,fr; => it-it)
  const COUNTRYLESS_WEIGHT = 0.98;

  public static function find_best_lang($accept_language){
        //dummy fallbacks
    $lang_list = array_keys(self::get_languages_list(array('used_for_website' => sql::true)));
    if(count($lang_list) == 1)
      return $lang_list[0];

    $lang_filtered = array();
    $lang_order    = array();

    $accept_language_mask = '#([a-z]{2}(?:-[a-z]{2})?)(?:\s*;q=([0-9.]+))?\s*,#';
    $valid_lang_mask      = '#^[a-z]{2}(?:-[a-z]{2})?$#';

    if(preg_match_all($accept_language_mask, strtolower("$accept_language,"), $out)) {
        if(!$out[2][0]) $out[2][0]=1;     //defaut q=1
        $lang_order = array_combine($out[1], $out[2]);
        $lang_order = self::correct_lang_weights($lang_order);
        foreach($lang_order as &$pow) $pow+=1;
             //weights <1 are bad since they cannt be easily multiplied
    }

    foreach($lang_order as $lang_want=>$lang_weight){
        $lang_want_root = substr($lang_want,0,2);
        foreach($lang_list as $lang_key){
            if(! preg_match($valid_lang_mask, $lang_key))
                continue;

            $lang_key_root = substr($lang_key, 0, 2);

            if($lang_want == $lang_key) $lang_filtered[$lang_key][] = $lang_weight;
            elseif($lang_key_root == $lang_want_root )
                $lang_filtered[$lang_key][] = $lang_weight * self::COUNTRYLESS_WEIGHT;
            else $lang_filtered[$lang_key][]=0; 
        }
    }


    $lang_filtered = array_map("max", $lang_filtered);
    return $lang_filtered?array_search( max($lang_filtered),$lang_filtered):'en-us';
         //last fallback (bad config)
  }

}
