<?
/*

*/

class locales_manager {


  public static function init(){
    if(!classes::init_need(__CLASS__)) return;

    $base = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    $accept_hash = md5($base);

    if(!$user_lang = $_SESSION['langs'][$accept_hash]) {
        $user_lang =  self::find_best_lang($base, exyks::retrieve('LANGUAGES'));
        $_SESSION['langs'][$accept_hash] = $user_lang;
    }

    define('USER_LANG', $user_lang);

    if(yks::$get->config->dyn_entities)
      foreach(yks::$get->config->dyn_entities->children() as $entity_def)
        locales_processor::register($entity_def->getName(), array("locales_sql_scanner", 'render'));
  }

  public static function translate($str, $lang = USER_LANG){
    $entities = array_merge(
        yks::$get->get("entities", $lang),
        retrieve_constants(CONST_LOCALES, "&%s;")
    ); foreach(tpls::$entities as $k=>$v) $entities["&$k;"] = $v;
    if($entities){while($tmp!=$str){ $tmp=$str; $str=strtr($str,$entities);} $str=$tmp;}
    
    if(strpos($str,"&")!==false)$str = locales_processor::process_entities($str, $lang);

    if(preg_match(MASK_INVALID_ENTITIES, $str)) {
        error_log("There are invalid entities in your document");
        $str = preg_replace(MASK_INVALID_ENTITIES,'&amp;',$str);
        if(preg_match("#<!\[CDATA\[(?s:.*?)\]\]>#",$str,$out)){
          $str= str_replace($out[0], str_replace("&amp;",'&',$out[0]),$str );
        }
    }

    return $str;
  }


  public static function find_best_lang($accept_language, $lang_list){
    if(!$lang_list) return 'en-us';
    $langs_nb = count($lang_list)-1;
    if(!$langs_nb) return $lang_list[0];

    $lang_filtered=array(); $lang_order=array();

    $accept_language_mask='#([a-z]{2}(?:-[a-z]{2})?)(?:\s*;q=([0-9.]+))?\s*,#';
    if(preg_match_all($accept_language_mask,strtolower("$accept_language,"),$out)) {
        if(!$out[2][0]) $out[2][0]=1;     //defaut q=1
        $lang_order=array_combine($out[1],$out[2]);arsort($lang_order);
        foreach($lang_order as &$pow) $pow+=1;
             //weights <1 are bad since they cannt be easily multiplied
    }

    foreach($lang_order as $lang_want=>$lang_weight){
        $lang_want_root = substr($lang_want,0,2);
    
        foreach($lang_list as $lang_order=>$lang_key){
            $lang_key_root = substr($lang_key,0,2);

            if($lang_want == $lang_key) $lang_filtered[$lang_key][]=$lang_weight;
            elseif($lang_key_root == $lang_want_root)$lang_filtered[$lang_key][]=0.8*$lang_weight;
            else $lang_filtered[$lang_key][]=($langs_nb-$lang_order)/$langs_nb; 
        }
    }
    $lang_filtered=array_map( "max", $lang_filtered);
    return $lang_filtered?array_search( max($lang_filtered),$lang_filtered):'en-us';
         //last fallback (bad config)
  }

}