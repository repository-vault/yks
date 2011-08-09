<?php

/*

*/

class locales_manager {
  const CONST_LOCALES_MASK = '#^(?!FLAG_).*(?<!_PATH|_MASK)$#';

  public static function init(){
    if(!classes::init_need(__CLASS__)) return;


    $base = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    $current_hash = md5($base);

    if($_SESSION['langs']['current_hash'] != $current_hash) {
        $_SESSION['langs']['current_hash'] = $current_hash;
        $user_lang =  self::find_best_lang($base, exyks::retrieve('LANGUAGES'));
        $_SESSION['langs']['current'] = $user_lang;
    }

    $user_lang = $_SESSION['langs']['current'];
    exyks::store("USER_LANG", $user_lang);

    if(yks::$get->config->dyn_entities)
      foreach(yks::$get->config->dyn_entities->children() as $entity_def)
        locales_processor::register($entity_def->getName(), array("locales_sql_scanner", 'render'));
  }

  public static function translate($str, $lang_key = false){
    if(!$lang_key) $lang_key = exyks::retrieve("USER_LANG");


    $entities = data::load("entities", $lang_key);
    if(!$entities) $entities = array();

    if(bool(yks::$get->config->locales['babelmode']))
      $entities = array_merge($entities, data::load("entities", "babel"));

    $entities = array_merge(
        array('&USER_LANG;' => $lang_key),
        $entities,
        retrieve_constants(self::CONST_LOCALES_MASK, "&%s;")
    ); foreach(tpls::$entities as $k=>$v) $entities["&$k;"] = $v;
    if($entities){while($tmp!=$str){ $tmp=$str; $str=strtr($str,$entities);} $str=$tmp;}

    if(strpos($str,"&")!==false)$str = locales_processor::process_entities($str, $lang_key);

    if(preg_match(MASK_INVALID_ENTITIES, $str)) {
        syslog(LOG_NOTICE, "There are invalid entities in your document");
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

  public static function find_best_lang($accept_language, $lang_list){

        //dummy fallbacks
    if(!$lang_list) return 'en-us';
    $langs_nb = count($lang_list)-1;
    if(!$langs_nb) return $lang_list[0];

    $lang_filtered = array();
    $lang_order    = array();

    $accept_language_mask='#([a-z]{2}(?:-[a-z]{2})?)(?:\s*;q=([0-9.]+))?\s*,#';
    if(preg_match_all($accept_language_mask,strtolower("$accept_language,"),$out)) {
        if(!$out[2][0]) $out[2][0]=1;     //defaut q=1
        $lang_order = array_combine($out[1], $out[2]);
        $lang_order = self::correct_lang_weights($lang_order);
        foreach($lang_order as &$pow) $pow+=1;
             //weights <1 are bad since they cannt be easily multiplied
    }

    foreach($lang_order as $lang_want=>$lang_weight){
        $lang_want_root = substr($lang_want,0,2);
        foreach($lang_list as $lang_key){
            $lang_key_root = substr($lang_key, 0, 2);

            if($lang_want == $lang_key) $lang_filtered[$lang_key][] = $lang_weight;
            elseif($lang_key_root == $lang_want_root)
                $lang_filtered[$lang_key][] = $lang_weight * self::COUNTRYLESS_WEIGHT;
            else $lang_filtered[$lang_key][]=0; 
        }
    }


    $lang_filtered = array_map("max", $lang_filtered);
    return $lang_filtered?array_search( max($lang_filtered),$lang_filtered):'en-us';
         //last fallback (bad config)
  }

}
