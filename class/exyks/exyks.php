<?php

/*  "Exyks controller" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2009
*/

class exyks {

  static public $headers;

  static public $href_ks;
  static public $page_def = 'home';
  static public $href;

  static private $is_script = false;
  static private $customs = array();

  static function init(){
    if(class_exists('classes') && !classes::init_need(__CLASS__)) return;

    global $action, $config;

    $action   = (string)is_array($_POST['ks_action'])?key($_POST['ks_action']):$_POST['ks_action'];

    $tmp = (string)$config->site['default_mode'];
    define('DEFAULT_MODE', $tmp?$tmp:"xml");

    include CLASS_PATH."/exyks/browser.php"; //define $engine

    $default = ROBOT||IPOD ? "html" : DEFAULT_MODE;
    $mode =  isset($_SERVER['HTTP_SCREEN_ID']) || isset($_POST['jsx']) ?  "jsx" : $default;
    define('JSX', $mode == "jsx");

    define('XSL_ENGINE', $engine);

    self::store('RENDER_MODE', JSX?"jsx":"full");
    self::store('RENDER_SIDE', ($mode=="html"?"server":"client")); //rbx is a render_side too

    if(IE6) exyks::store('RENDER_START', '<!DOCTYPE');

    self::$headers = array(
        'full-server'=>ROBOT?TYPE_XHTML:TYPE_HTML,
        'full-client'=>TYPE_XML,
        'jsx-client'=>TYPE_XML,
    );

    self::store('LANGUAGES', preg_split(VAL_SPLITTER, $config->locales['keys'], -1, PREG_SPLIT_NO_EMPTY));
    define('JSX_TARGET', $_SERVER['HTTP_CONTENT_TARGET']);


    $client_xsl =                   "xsl/{$engine}_client.xsl"; // relative
    self::store('XSL_URL',         CACHE_URL.'/'.$client_xsl);
    self::store('XSL_SERVER_PATH', CACHE_PATH."/xsl/{$engine}_server.xsl");
    self::store('XSL_CLIENT_PATH', CACHE_PATH.'/'.$client_xsl);
  }


  static function bench($key) { return self::store("time_$key", microtime(true)); }
  static function tick($key,$now=false) {
    return ($now?self::bench($now):microtime(true)) - self::retrieve("time_$key");
  }


    //need 5.3 late static binding self::_class (extend storage)
  static protected $_storage = array();
  static function store($key, $value){ return self::$_storage[$key]=$value; }
  static function retrieve($key,$fallback=false){
    return isset(self::$_storage[$key])?self::$_storage[$key]:$fallback;
  }



  static function register($tagName, $callback){
    self::$customs[$tagName] = $callback;
  }

  static function parse($doc){
    if(!self::$customs) return;

    $xpath = new DOMXPath($doc);
    $query = mask_join("|",array_keys(self::$customs), "//%s");
    $entries = $xpath->query($query);
    if(!$entries->length) return;

    foreach ($entries as $entry) {
        $nodeName = $entry->nodeName;
        $callback = self::$customs[$nodeName];
        if($callback)
            call_user_func($callback, $doc, $entry);
    }
  }

    //website initialisation
  static function context_prepare($query_path){
    $config  = yks::$get->config;

    define('COMMONS_PATH', paths_merge(ROOT_PATH, $config->site['commons_path']));
    define('COMMONS_URL',$config->site['commons_url']);

    chdir(ROOT_PATH); //we are now in root path (not in www_path any more)

    include CLASS_PATH."/functions.php";

    exyks_paths::register("/Yks", "path://yks//Yks");
    data::register('types_xml',   array('myks', 'get_types_xml'));
    data::register('tables_xml',  array('myks', 'get_tables_xml'));
    data::register('entities',    array('locales_fetcher', 'retrieve'));

    $parsed_paths = exyks_paths::parse($query_path);
    self::$is_script = substr(self::$href_ks,0,13)=="/Yks/Scripts/";

    if(self::$is_script) array_shift($parsed_paths[0]);
    else self::website_prepare($config);

    return $parsed_paths;
  }

  static function website_prepare($config){
    rbx::$output_mode = 0;

        //head element creation
    if(!$config->head)          $config->addChild("head");
    if(!$config->head->jsx)     $config->head->addChild("jsx");
    if(!$config->head->styles)  $config->head->addChild("styles");
    if(!$config->head->scripts) $config->head->addChild("scripts");

    exyks_session::init_core();
    exyks_security::sanitize();
    locales_manager::init();

    if(! bool($config->users['custom_session_manager']))
        exyks_session::load_classic();



    if(JSX){
        tpls::top('Yks/jsx_top', tpls::STD, "jsx");
        tpls::bottom('Yks/jsx_bottom', tpls::STD, "jsx");
    } else {
        tpls::top("Yks/top");
        tpls::bottom("Yks/bottom");
    }

  }

    //Ferme les subs : close session, shut SQL link down & co
  static function context_end(){
    if(exyks::$is_script) die;
    if(class_exists('sess')) sess::close();
    exyks::store('generation_time', exyks::tick('generation_start'));

    $str = ob_get_contents(); ob_end_clean(); //did subs provide contents ?
    if(DEBUG && $str){ header(TYPE_TEXT);die($str.print_r(sql::$queries,1)); }
    if(JSX && jsx::$rbx)jsx::end();
  }

  static function render_prepare($vars = array()){
    extract($vars);
    tpls::export($vars); //!

    exyks::bench('display_start'); 
    ob_start();


    jsx::set(array(
      "href"      => "?".exyks::$href,
      "href_ks"   => "?".exyks::$href_ks,
      "href_base" => "?$href_base",
    ));

    if(!JSX) {
        jsx::set(array(
          "xsl_engine" => XSL_ENGINE,
          "site_code"  => SITE_CODE,
          "cache_path" => CACHE_REL,
          "href_fold"  => "?$href_fold",
          "screen_id"  => 10
        ));

        $meta = yks::$get->config->head->addChild("meta");
        list($header, $value) = explode(':', TYPE_HTML);
        $meta->addAttribute("http-equiv", $header); $meta->addAttribute("content", $value);
    }

    if(!tpls::$body) tpls::body($subs_file);


    tpls::top('Yks/xml_head', tpls::TOP, 'all');
  }


  static function render($str){

    $str = locales_manager::translate($str);

    if(DEBUG) $str.=sys_end( exyks::retrieve('generation_time'), exyks::tick('display_start'));

    $render_mode  = exyks::retrieve('RENDER_MODE');
    $render_side  = exyks::retrieve('RENDER_SIDE');
    $render_style = "$render_mode-$render_side";
    $xsl_client   = exyks::retrieve('XSL_CLIENT_PATH');
    $xsl_server   = exyks::retrieve('XSL_SERVER_PATH');

    header(self::$headers[$render_style]);
    header("Cache-Control: no-cache");

    if(true || self::$customs || $render_side=="server"){ // || optim XML
        $doc = xml::load_string($str);
        if(!$doc) yks::fatality(yks::FATALITY_XML_SYNTAX, $str, $render_mode);
        exyks::parse($doc);
        if($render_side=="client") $str = $doc->saveXML();
    }
    
    if($render_side == "client"){
        if($xsl_client && !is_file($xsl_client))
            yks::fatality(yks::FATALITY_XSL_404, "xsl file is missing : $xsl_client",  $render_mode);
        die($str);
    }

    if($render_side == "server"){
        if(!is_file($xsl_server))
            yks::fatality(yks::FATALITY_XSL_404, "xsl file is missing : $xsl_server");
        $render_start = exyks::retrieve('RENDER_START');
        $doc = xsl::resolve($doc, $xsl_server);
        $contents = $doc->saveXML();
        if($render_start) die(strstr($contents, $render_start));
        die($contents);
        $content = preg_replace('#\s+xmlns:[a-z]+=".*?"#',"",$str);
    }
  }


}
