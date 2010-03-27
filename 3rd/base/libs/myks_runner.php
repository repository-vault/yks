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
        return yks_runner::httpd_tunnel(__CLASS__, "manage_types");

    files::delete_dir(XML_CACHE_PATH, false); //cleaning...
    files::create_dir(XML_CACHE_PATH);        //... and go

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
* Update XSL caches & files
* @alias xsl
*/
  function manage_xsl(){

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
        return yks_runner::httpd_tunnel(__CLASS__, "manage_locales");

    rbx::title("Starting localization");

    $result = locales_fetcher::fetch_all();
    cli::box("Paths", locales_fetcher::$locale_paths);
    if(!$result)
        rbx::error("Please define at least one language");
    else foreach($result as $infos)
        rbx::ok("Entities {$infos[0]} reloaded ({$infos[1]})");

    rbx::line();
  }




}
