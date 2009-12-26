<?php


class myks_runner {

  var $xml_filename;
  var $xsl_filename;

  var $mykse_file_path;
  var $mykse_file_url;

  var $browsers_engines;
  var $rendering_sides;


  static function init(){
    if(!classes::init_need(__CLASS__)) return;

    define('XML_CACHE_PATH',  CACHE_PATH."/xml");
    define('XSL_CACHE_PATH',  CACHE_PATH."/xsl");
  }

  function __construct(){
    $this->xml_filename     = RSRCS_PATH."/xsl/root.xsl";              //meta XSL source
    $this->xsl_filename     = RSRCS_PATH."/xsl/metas/xsl_gen.xsl";     //meta XSL stylesheet

    $this->mykse_file_path  = XML_CACHE_PATH."/myks.xml";
    $this->mykse_file_url   = SITE_URL."/".ltrim(end(explode(WWW_PATH, $this->mykse_file_path,2)),"/");

    $this->browsers_engines = array( 'trident', 'gecko', 'webkit', 'presto');
    $this->rendering_sides  = array( 'server', 'client');

  }


  function go(){
    $this->manage_types();
    $this->manage_locales();
    $this->manage_xsl();
    $this->manage_sql();
  }

  function sql(){
    interactive_runner::start(new sql_runner());
  }

  function manage_sql($run_queries = false){
    try {
        $sql = new sql_runner();
        $sql->go($run_queries);
    } catch(Exception $e){
        rbx::error("Sql management failure");
    }
  }


/**
* parse&update mykse XML
* @alias types
*/
  function manage_types(){
    if(PHP_SAPI == "cli")
        return $this->httpd_tunnel("manage_types");

    self::cache_dir_check();

    rbx::title("Parsing myks definitions");

    $types_xml      = data::reload("types_xml"); 
    $tables_xml     = data::reload("tables_xml");
    rbx::ok("APC types_xml/tables_xml cache reloaded");

    $types_xml->asXML($this->mykse_file_path);	// export
    rbx::ok("Myks types {$this->mykse_file_path} updated");

        //before export ? after ? i don't know is it's very usefull anymore
    if(!$types_xml->myks_type) {
        rbx::warn("Unable to locate type : 'myks_type', skipping");
    }

    rbx::line();
  }


/**
* cli tunneling for APC related features
*/
  function httpd_tunnel($command){
    self::http_auto_check(); //check self http lookup

    $myks_http_url = SITE_URL."/?/Yks/Scripts/Myks|$command";
    $http_contents = self::wget_dnsless($myks_http_url);
    rbx::ok("Running $myks_http_url");
    echo $http_contents;
  }


/**
* Update XSL caches & files
* @alias xsl
*/
  function manage_xsl(){

    self::cache_dir_check();
    rbx::title("Updating XSL cache files");

    files::delete_dir(XSL_CACHE_PATH, false); //cleaning...
    files::create_dir(XSL_CACHE_PATH);        //... and go


    $doc = new DOMDocument("1.0");
    $xsl = new XSLTProcessor();
    $doc->load($this->xsl_filename, LIBXML_NOENT);
    $xsl->importStyleSheet($doc);

    $doc->load($this->xml_filename);

    $xsl_cache = new xsl_cache($doc, $xsl);

    $xsl_cache->parameters_add( array(
        'mykse_file_path' => $this->mykse_file_path,
        'mykse_file_url'  => $this->mykse_file_url,
    ));

    foreach($this->browsers_engines as $engine_name)
      foreach($this->rendering_sides as $rendering_side) {
        $file = $xsl_cache->out($engine_name, $rendering_side); 
        rbx::ok("$engine_name ($rendering_side) reloaded into $file");
    }

    $out_file = xsl_cache::out_file("robot", "server");
    copy(RSRCS_PATH."/xsl/specials/validator.xsl", $out_file); 
    rbx::ok("robot (server) reloaded into $out_file");

     rbx::line();
  }

/**
* Update locales
* @alias locales
*/
  function manage_locales(){

    if(PHP_SAPI == "cli")
        return $this->httpd_tunnel("manage_locales");

    rbx::title("Starting localization");

    $result = locales_fetcher::fetch_all();
    if(!$result)
        rbx::error("Please define at least one language");
    else foreach($result as $infos)
        rbx::ok("Entities {$infos[0]} reloaded ({$infos[1]})");

    rbx::line();
  }

/**
* Check caches
* @alias caches
*/
  private static function cache_dir_check(){
    try {
        files::create_dir(CACHE_PATH);
        if(!is_writable(CACHE_PATH))
            throw rbx::error(CACHE_PATH." is not writable");

        files::create_dir(XML_CACHE_PATH);

        return ; //!
        $me = trim(`id -un`).':'.trim(`id -gn`); $me_id = trim(`id -u`);

        $cache_owner = fileowner(CACHE_PATH);
        if($cache_owner!=$me_id)
            rbx::error("Please make sure cache directory :'".CACHE_PATH."' is owned by $me");

    } catch(Exception $e){ rbx::error("Unable to access cache directory '".CACHE_PATH."'"); die; }
  }


  private static function wget_dnsless($url){
    $local_ip      = (string) yks::$get->config->site->local['ip'];
    return http::ping_url($url, 3, $local_ip);
  }


/**
*  Open a http lnk on itself, and search for a ping file
*  Check whenever cli is able to talk to httpd
*/
  public static function http_auto_check() {
    self::cache_dir_check();

    $ping_file = CACHE_PATH."/ping";
    $ping_url  = CACHE_URL."/ping";
    $rnd       = md5(rand());
    $res = file_put_contents($ping_file, $rnd);
    if(!$res)
        throw new Exception("Make sure cache directory '".CACHE_PATH."' is world writable");
 
    $http_contents = self::wget_dnsless($ping_url);
    unlink($ping_file);

    if($http_contents != $rnd) {
        rbx::box("http_contents ($ping_url)", $http_contents, "rnd ($ping_file)", $rnd);
        throw new Exception("Http self check failed, please make sure <site><local @ip/> is well configured");
    }
    return true;
  }


}
