<?php


class yks
{
  static public $get;
  static public $href_ks;
  static public $page_def = 'home';
  static public $href;
  static public $is_script = false;

  static function init(){
    self::$get = new yks();
  }

  public function get($key, $args = false){ //dont use it as a static, use yks::$get->get(
    $flag = $args?"$key_$args":$key;
    if(isset($this->$flag)) return $this->$flag;
    if($key == "tables_xml")
        $this->$flag = data::load($key);

    if($key == "types_xml")
        $this->$flag = data::load($key);

    if($key == "config")
        $this->$flag = config::$config;

    if($key == "entities")
        $this->$flag = data::load($key,$args);

    return $this->$flag;
  }

  private function __get($key){ return $this->get($key);  }


/*
  Prepare the passed URL to be used in the whole yks layer
    split & prepare args,  /Admin/Users//3123/Manage/Addrs//23/manage
    return context = array( array(
                $subs_fold  // path to the included file
                $file,      // current node/leaf name
                $args,      // args tuple for current node
                $href_fold, // recombined path to current node
                $href_base, // href_fold, with current args
      , ... ) used as tuples in index.php.
    )
   ";" is not really the smartest args separator since escaped XML entities are in a &..; pattern,
    what conflict easily with ";" separator, we remplate it by | to split here
    fyi : $args=array_slice(preg_split("#(?<!.&lt|&amp|.&gt);#", ";$args_str;;;"),1); does the trick, but negative lookbehind assertions cannot have a variable length
*/

  static function prepare($url){
    $tmp = htmlspecialchars(strtr(urldecode($url),';','|'),ENT_QUOTES,'utf-8');
    yks::$href_ks = strtr($tmp, '|', ';');
    yks::$is_script = substr(yks::$href_ks,0,13)=="/Yks/Scripts/"; //no args on scripts
    $url_tree  = self::url_tokenize_single_slash($tmp);


    if(!$url_tree) //FALLBACK si url = '/'
         $url_tree= array(array(1=>ucfirst(SITE_CODE)));
        //rajout systematique d'une feuille pour ne pas finir sur un noeud
    $url_tree[] = array();

    $result_path = array( array("","main") );
    $subs_path = "subs";
    $href_fold = "";
    $href_base = "";

    foreach($url_tree as $tmp){
        list($node_name, $args_str) = array($tmp[1],$tmp[2]); 
        $args = explode('|', "$args_str||||");

            //sanitize all malicious attempts '/Admin/../config...'
        if(preg_match("#[.]#",$node_name) || $node_name=="main")
            return yks::prepare('/');
            //on s'arrete si l'on est plus dans un noeud
        if(!$node_name || !is_dir($subs_path.="/$node_name"))
            break;

        if($href_base)
            $href_fold = $href_base; 
        $result_path[] = array(
                $subs_fold=substr($subs_path,4),
                "main",
                $args,
                $href_fold.="/$node_name",
                $href_base = $args_str?"$href_fold//$args_str":$href_fold //cosmetique
        );
    }
    yks::$href="$href_base/$node_name";
        //si on a pas fini sur une feuille, utilisation de feuille par default
    if(!$node_name)
        $node_name = &yks::$page_def;
    $result_path[] = array($subs_fold, &$node_name, $args, $href_fold, $href_base);

        /* $context, $href, $href_ks, $context_depths */
    return array($result_path, self::$href, self::$href_ks, count($result_path) - 1);
  }


/*
    Split une QUERY_STRING /Admin/Users//3123/Manage/Addrs//23/manage
    en [[/Admin], [/Users, 3123] ,  [/Manage],  [/Addrs,23], [/manage] ]
    ( regroupement des blocs avec leurs parametres )
*/
    
  static function url_tokenize_single_slash($url) {
    preg_match_all("#/([^/]+)(?://([^/]*))?#", $url, $tree,PREG_SET_ORDER);
    return $tree;
  }

} yks::init();




