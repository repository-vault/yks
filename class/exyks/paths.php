<?php


class exyks_paths {

  const prefix_ns = "paths_prefix";
  static private $paths_ns = array();
  private static $paths = array();

/*
    Scan config file for paths definitions and register them
    <paths xmlns:pf="paths_prefix" pf:admin="."
*/
  public static function init(){
    if(!classes::init_need(__CLASS__)) return;

    $config = yks::$get->config->search("paths", false);
    $prefixes = $config ? attributes_to_assoc($config, self::prefix_ns) : array();
    self::$paths_ns = array_merge($prefixes, array(
        'yks'   => BASE_PATH,
        'here'  => ROOT_PATH,
        '3rd'   => THRD_PATH,
    ));
    if($config) foreach($config->path as $path){
        foreach(explode(':', $path['symbolic']) as $path_key) {
            $dest = $path['base']?$path['base']."/$path_key":$path['dest'];
            self::register($path_key, $dest);
        }
    }
  }


/*
    Register a "virual" path from any file starting with $path_key
*/
  public static function register($path_key, $path){
        //resolve
    $mask = '#^path://('.join('|',array_keys(self::$paths_ns)).')(.*?)(//|$)#ie'; //cooool

    $repl = 'self::$paths_ns["$1"]."$2"';
    $sub_path = paths_merge(ROOT_PATH, preg_replace($mask, "$repl.'/subs/'", $path));
    $tpl_path = paths_merge(ROOT_PATH, preg_replace($mask, "$repl.'/tpls/'", $path));

    self::$paths['subs'][$path_key]   = $sub_path;
    tpls::add_resolver($path_key, $tpl_path);
  }



  private static function resolve($subs_fold, $node_name, $subs_path){
    if($subs = self::$paths['subs']["$subs_fold/$node_name"]) {
        return $subs;
    }
    return "$subs_path/$node_name";
  }


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
    //DONT CORRECT THIS TO SUPPORT &#160;, send proper %C2%A0 instead
*/

  static function parse($url){

    $href_ks = htmlspecialchars(strtok(urldecode($url), "|"),ENT_QUOTES,'UTF-8');
    preg_match_all("#/([^/]+)(?://([^/]*))?#", $href_ks, $url_tree, PREG_SET_ORDER);

    if(!$url_tree) //FALLBACK si url = '/'
         $url_tree= array(array(1=>ucfirst(SITE_CODE)));
        //rajout systematique d'une feuille pour ne pas finir sur un noeud
    $url_tree[] = array();

    $result_path = array();

    $subs_path = ROOT_PATH."/subs";
    $subs_fold = "";
    $href_fold = "";
    $href_base = "";

    $zero_args = array(substr($href_ks, 0,strcspn($href_ks, "/"))); //should be expode ;
    $result_path[] = array($subs_path, $subs_fold, "main", $zero_args);

    $value = strtok("|");

    foreach($url_tree as $tmp){
        list($node_name, $args_str) = array($tmp[1],$tmp[2]); 
        $args = self::parse_args($args_str);

            //sanitize all malicious attempts '/Admin/../config...'
        if(preg_match("#[.]#",$node_name) || $node_name=="main")
            return self::parse('/');

        $href_key  = "$subs_fold/$node_name";

        $tmp_path = self::resolve($subs_fold, $node_name, $subs_path);

            //on s'arrete si l'on est plus dans un noeud
        if(!$node_name || !is_dir($tmp_path)) 
            break;

            //on ne modifie le path que si l'on est dans un noeud
        $subs_path = $tmp_path;


        $subs_fold .= "/$node_name";
        if($href_base) $href_fold = $href_base; 
        $href_fold.="/$node_name";

        $result_path[] = array(
                $subs_path,
                $subs_fold,
                "main",
                $args,
                $href_fold,
                $href_base = $args_str?"$href_fold//$args_str":$href_fold //cosmetique
        );
    }


    $href = "$href_base/$node_name";
        //si on a pas fini sur une feuille, utilisation de feuille par default
    if(!$node_name)
        $node_name = &exyks::$page_def;

    $result_path[] = array($subs_path, $subs_fold, &$node_name, $args, $href_fold, $href_base);


        /* $result_path, $href, $href_ks, $context_depths */
    $res = array($result_path, $href, $href_ks, count($result_path) - 1, $value);
    return $res;
  }

  static function parse_args($args_str){
     return explode(';', "$args_str;;;;");
  }


}
