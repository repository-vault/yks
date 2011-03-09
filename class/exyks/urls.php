<?php

class exyks_urls {

  private static $paths;

  public static function init(){

    foreach(yks::$get->config->paths->iterate("path") as $path) {
        $root = $path['root'] ? exyks_paths::resolve($path['root']) : ROOT_PATH;
        self::register(false, $path['virtual'], $root, $path['dest']);
    }

    //here

    foreach(exyks::get_modules_list() as $module) {
        foreach($module->virtual_paths as $path_key=>$dest)
            self::register($module, $path_key, $dest[0], $dest[1]);
    }

  }


/*
    Register a "virtual" path from any file starting with $path_key
*/
  private static function register($module, $path_key, $root, $dest){


    $sub_path = paths_merge(ROOT_PATH, "$root/subs/$dest");
    $tpl_path = paths_merge(ROOT_PATH, "$root/tpls/$dest");

    self::$paths[$path_key] = compact('sub_path', 'module');

    tpls::add_resolver($path_key, $tpl_path);
  }


  private static $current;
    //only used in exyks::$get->module
  public static function get_current_module(){
    return self::$current;
  }

  private static function resolve($subs_fold, $node_name, $subs_path){

    if($subs = self::$paths["$subs_fold/$node_name"]) {
        self::$current = $subs['module'];
        return $subs['sub_path'];
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
    $url = explode("|", urldecode($url), 2);
    $href_ks = htmlspecialchars($url[0],ENT_QUOTES,'UTF-8');
    preg_match_all("#/([^/]+)(?://([^/]*))?#", $href_ks, $url_tree, PREG_SET_ORDER);

    if(!$url_tree) //FALLBACK si url = '/'
         $url_tree= array(array(1=>SITE_BASE));
        //rajout systematique d'une feuille pour ne pas finir sur un noeud
    $url_tree[] = array();

    $result_path = array();

    $subs_path = ROOT_PATH."/subs";
    $subs_fold = "";
    $href_fold = "";
    $href_base = "";

    $zero_args = self::parse_args(substr($href_ks, 0,strcspn($href_ks, "/")));
    $result_path[] = array($subs_path, $subs_fold, "main", $zero_args);


    $value = $url[1];
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
