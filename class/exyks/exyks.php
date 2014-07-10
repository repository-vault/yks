<?php

/*  "Exyks controller" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2009
*/

class exyks {

  public static $headers;

  public static $href_ks;
  public static $page_def = 'home';
  public static $href;
  public static $head = null;

  private static $is_script = false;
  private static $modules_list;
  public static $get;

  public static $REMOTE_ADDR;
  public static $CLIENT_ADDR;
  public static $COMPATIBILITY;

  public static $vars = array();

  static function get_modules_list(){    return self::$modules_list;  }

  const COMPATIBLITY_HTML5  = 'html5';
  const COMPATIBLITY_QUIRKS = 'quirks';

  static function init() {

    if(!defined('YKS_FUNCTIONS'))
      require CLASS_PATH."/functions.php";

    if(class_exists('classes') && !classes::init_need(__CLASS__))
        return; //exyks::init can be called

    if(bool((string)yks::$get->config->site['closed'])) {
        error_log($_SERVER['REMOTE_ADDR']);
        if(PHP_SAPI!='cli' && !yks::$get->config->is_debug())
            yks::fatality(yks::FATALITY_SITE_CLOSED);
    }

    if(!yks::$get->config)
        yks::fatality(yks::FATALITY_CONFIG, "Exyks configuration not found");


    $trusted_proxies = array();
    foreach(yks::$get->config->security->iterate('trusted_proxy') as $proxy)
      $trusted_proxies[] = $proxy['addr'];

    self::$CLIENT_ADDR   = http::client_addr($trusted_proxies);
    self::$REMOTE_ADDR   = $_SERVER['REMOTE_ADDR'];
    self::$COMPATIBILITY = pick(yks::$get->config->themes['compatibility'], self::COMPATIBLITY_HTML5);


    chdir(ROOT_PATH); //we are now in root path (not in www_path any more)

    data::register('types_xml',   array('myks', 'get_types_xml'));
    data::register('tables_xml',  array('myks', 'get_tables_xml'));
    data::register('entities',    array('locales_fetcher', 'retrieve'));

    define('SESSION_NAME',  crpt(self::$REMOTE_ADDR, FLAG_SESS, 10)."_yks");

    define('USERS_ROOT',     (int)yks::$get->config->users['root']);
    self::store('USERS_ROOT', USERS_ROOT); //drop constants here


    if(yks::$get->config->site['hostname']) {
        $hostname = trim(`hostname`);
        if(yks::$get->config->site['hostname'] != $hostname)
          yks::fatality(yks::FATALITY_CONFIG, "Invalid local host name {$hostname}");
    }

    self::$get = new self();

    self::$modules_list = array();
    if(!SITE_STANDALONE)
      self::$modules_list[] = new exyks_module(array(
        'key'      => "base",
        'manifest' => "path://yks/3rd/base",
    ));

    foreach(yks::$get->config->modules->iterate("module") as $module){
      try{
        self::$modules_list[] = new exyks_module($module);
      }catch(UnresolvedOptionalModule $e){
        // Just skip errors on optional modules
      }
    }

    self::extends_include_path();
  }


  public static function web_init(){

    global $action;

    $action   = (string)is_array($_POST['ks_action'])?key($_POST['ks_action']):$_POST['ks_action'];

    $tmp = (string)yks::$get->config->site['default_mode'];
    define('DEFAULT_MODE', $tmp?$tmp:"xml");

    include CLASS_PATH."/exyks/browser.php"; //define $engine

    $default = ROBOT||PLATFORM_MOBILE ? "html" : DEFAULT_MODE;
    $mode =  isset($_SERVER['HTTP_YKS_JSX']) || isset($_POST['jsx']) ?  "jsx" : $default;
    define('JSX', $mode == "jsx");

    define('XSL_ENGINE', $engine);

    self::store("USER_TZ", IDATEZ);
    self::store('RENDER_MODE', JSX?"jsx":"full");
    self::store('RENDER_SIDE', ($mode=="html"||PLATFORM_MOBILE)?"server":"client" ); //rbx is a render_side too

    if(IE6) exyks::store('RENDER_START', '<!DOCTYPE');

    self::$headers = array(
        'full-server'=>ROBOT?TYPE_XHTML:TYPE_HTML,
        'full-client'=>TYPE_XML,
        'jsx-client'=>TYPE_XML,
        'jsx-server'=>TYPE_XML,//no TYPE_XHTML as it's irrelevant (DOM is more important)
    );


    if(yks::$get->config->site['render'] == 'inline')
      tpls::register_custom_element("box[@src]", array(__CLASS__, 'inline_box'));


    tpls::register_custom_element("field", array('tpls', 'inline_field'));


    define('JSX_TARGET', $_SERVER['HTTP_CONTENT_TARGET']);
    define('FLAG_UPLOAD',    yks::$get->config->flags['upload'].FLAG_DOMAIN);

    define('BASE_CC',        yks::$get->config->lang['country_code']);
    define('ERROR_PAGE',     '/'.SITE_BASE.'/error');
    define('ERROR_404',      "Location: /?".ERROR_PAGE.'//404');

    self::store('XSL_URL',         CACHE_URL."/xsl/{$engine}_client.xsl");
    self::store('XSL_SERVER_PATH', "path://cache/xsl/{$engine}_server.xsl");
    self::store('XSL_CLIENT_PATH', "path://cache/xsl/{$engine}_client.xsl");
  }


  private static function extends_include_path(){
    $base = array_extract(yks::$get->config->paths->iterate("include"), "path");
    foreach($base as &$path)
        $path = exyks_paths::resolve($path);
    classes::extend_include_path($base);
  }


  static function bench($key) { return self::store("time_$key", microtime(true)); }
  static function tick($key,$now=false, $flag = false) {
    $time = ($now?self::bench($now):microtime(true)) - self::retrieve("time_$key");
    if($flag)
        echo "$flag : $time\r\n";
    return $time;
  }


    //need 5.3 late static binding self::_class (extend storage)
  static protected $_storage = array();
  static function store($key, $value){ return self::$_storage[$key]=$value; }
  static function retrieve($key,$fallback=false){
    return isset(self::$_storage[$key])?self::$_storage[$key]:$fallback;
  }


    //website initialisation
  static function context_prepare($query_path){
    self::web_init();
        /* $result_path, $href, $href_ks, $context_depths */
    $parsed_paths = exyks_urls::parse($query_path);
    list(, self::$href, self::$href_ks) = $parsed_paths;
    self::$is_script = substr(self::$href_ks,0,13)=="/Yks/Scripts/";

    if(self::$is_script) array_shift($parsed_paths[0]);
    else self::website_prepare();

    return $parsed_paths;
  }


  static function website_prepare(){
    global $config;
    rbx::$output_mode = 0;

        //head element creation
    self::$head = $config->head->asSimpleXML();

    if(!self::$head->jsx)     self::$head->addChild("jsx");
    if(!self::$head->styles)  self::$head->addChild("styles");
    if(!self::$head->scripts) self::$head->addChild("scripts");

    exyks_session::connect();
    exyks_security::sanitize();
    locales_manager::init();

    if(! bool($config->users['custom_session_manager']))
        exyks_session::load();


    if(bool((string)yks::$get->config->site['closed']))
        tpls::css_add("/css/".SITE_BASE."/off.css"); //not mandatory.., but could help

    tpls::$nav = new navigation();

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
    exyks_session::close();
    exyks::store('generation_time', exyks::tick('generation_start'));

    $str = ob_get_contents(); ob_end_clean(); //did subs provide contents ?
    if($str && yks::$get->config->is_debug() ) {
        header(TYPE_TEXT);
        die($str.print_r(sql::$queries,1));
    }
    if(JSX && jsx::$rbx) jsx::end();
  }

  static function render_prepare($vars = array()){
    extract($vars);
    tpls::export($vars); //!

    exyks::bench('display_start');
    ob_start();

    jsx::set(array(
      "href"      => "?".exyks::$href,
      "href_ks"   => "?".exyks::$href_ks,
      "href_base" => "?&href_base;",
    ));

    if(!JSX) {
        jsx::set(array(
          "xsl_engine" => XSL_ENGINE,
          "site_code"  => SITE_CODE,
          "cache_path" => CACHE_REL,
          "href_fold"  => "?&href_fold;",
          "lang_key"  =>  "&USER_LANG;",
          "screen_id"  => 10
        ));

        $meta = self::$head->addChild("meta");
        list($header, $value) = explode(':', TYPE_HTML);
        $meta->addAttribute("http-equiv", $header); $meta->addAttribute("content", $value);
    }

    if(!tpls::$body) tpls::body($subs_file);


    tpls::top('Yks/xml_head', tpls::TOP, 'all');
  }


    //inline controler for boxes
  public static function inline_box($doc, $elem){
    static $moved = array();

    $src = $href_ks = $elem->getAttribute("src");


    $domready_nodes = $doc->getElementsByTagName("domready");
      //move all domready before the requested box
    if($domready_nodes->length) foreach($domready_nodes as $domready){
      if(in_array($domready, $moved )) continue;
      $moved [] = $domready;
      $elem->parentNode->insertBefore($domready, $elem);
    }
      //echo $doc->saveXML();die;
    $src = strip_start($src, "/?");
    $src = strip_start($src, "?");

    list($context) = exyks_urls::parse($src);
    $included_files = get_included_files();

    foreach($context as $depth=>$infos){
        list($subs_path, $subs_fold, $page, $args, $href_fold, $href_base) = $infos;
        if($depth == $depths) exyks::$href = $href = "$href_base/$page";
        list($sub0, $sub1, $sub2, $sub3, $sub4) = $args;
        $subs_file = "$subs_fold/$page"; exyks::$page_def = "home";
        if(!is_file($file = "$subs_path/$page.php")) return;
        $mask = $file.join(',', $args);
        if(isset(exyks::$vars[$mask])) {
            foreach(exyks::$vars[$mask] as $var) global $$var;
            continue;
        }
        include $file;
    }

    $body = tpls::tpl($subs_file);
    ob_start();
    include $body;
    $str = ob_get_contents(); ob_end_clean();

    tpls::export(compact('href_base', 'href_fold', 'subs_file', 'href', 'href_ks'));

    //!href_base
    $str  = locales_manager::translate($str);
    $new_doc = xml::load_string(XML_VERSION.$str);
    if(!$new_doc) return;

    tpls::process_customs_elements($new_doc); //recurse

    $xpath = new DOMXPath($new_doc);
    $entries = $xpath->query("//div[@class='box']|//box");
    if(!$entries->length)
      return;

    $new_node = $entries->item(0);
    $new_node = $doc->importNode($new_node, true);

    foreach($elem->attributes as $attribute) {
      if($attribute->name == "src")
        $new_node->setAttribute("url", $href_ks);
      else if(!$new_node->hasAttribute($attribute->name))
        $new_node->setAttribute($attribute->name, $attribute->value);
    }


    $forms = $new_node->getElementsByTagName("ks_form");
    if($forms->length) foreach($forms as $form) {
      if(!$form->hasAttribute("action"))
        $form->setAttribute("action", $href_ks);
    }

    $parent = $elem->parentNode;
    $parent->replaceChild($new_node, $elem);

  }



  static function render($str){

    $str = locales_manager::translate($str);



    $render_mode  = exyks::retrieve('RENDER_MODE');
    $render_side  = exyks::retrieve('RENDER_SIDE');
    $render_style = "$render_mode-$render_side";
    $xsl_client   = exyks::retrieve('XSL_CLIENT_PATH');
    $xsl_server   = exyks::retrieve('XSL_SERVER_PATH');

    header(self::$headers[$render_style]);
    header("Cache-Control: no-cache");
    header("Yks-Render-Side: $render_side");

    if(true || self::$customs || $render_side=="server"){ // || optim XML
        $doc = xml::load_string($str);
        if(!$doc) yks::fatality(yks::FATALITY_XML_SYNTAX, $str, $render_mode);
        tpls::process_customs_elements($doc);
        if($render_side=="client") $str = $doc->saveXML();
    }

    if(yks::$get->config->is_debug())
        $str .= sys_end( exyks::retrieve('generation_time'), exyks::tick('display_start'));


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

        if($render_mode == "jsx")
            $contents = preg_replace("#<!DOCTYPE.*?>#", "", $contents);

        if($render_start)
            $contents = strstr($contents, $render_start);

        die($contents);
        $content = preg_replace('#\s+xmlns:[a-z]+=".*?"#',"",$str);
    }
  }


  private function get($key){ //dont use it as a static, use yks::$get->get(

    if(isset($this->$key)) return $this->$key;

    if($key == "module")
        return exyks_urls::get_current_module();

  }

  public function __get($key){ return $this->get($key);  }


}
