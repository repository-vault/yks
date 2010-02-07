<?php



class sql_runner {

  private $types_xml;
  private $tables_xml;

  private $procedures_xml;
  private $views_xml;

  private static $myks_config;
  private static $sql_ready = false;

  static function init(){
    if(class_exists('classes') && !classes::init_need(__CLASS__)) return;

    include_once CLASS_PATH."/myks/elements.php";
    include_once CLASS_PATH."/myks/generator.php";
    classes::register_class_path("pgsql_auto_inc_sync",
        CLASS_PATH."/myks/elements/drivers/pgsql/misc/auto_inc_sync.php");

    define('END', ";\r\n");

        // static config load
    self::$myks_config = yks::$get->config->myks;

    $privileges = self::$myks_config->privileges;
    privileges::declare_root_privileges($privileges);

    rbx::title("Configuring sql environnement");

        // sql link establishment
    try {
        $lnk_admin = (string) self::$myks_config['link_admin'];
        if(!$lnk_admin)
            throw rbx::error("Admin sql lnk cannot be found.");

        self::$sql_ready = true;
        sql::connect($lnk_admin);
        rbx::ok("Sql management is ready for on lnk '#$lnk_admin'");

    } catch(rbx $e) {
        self::$sql_ready = false;
        rbx::ok("Skipping SQL management");
    }

  }


  function __construct(){
    $this->init_engine();
  }

/**
*  @alias reinit
*/
  function init_engine(){
    if(!self::$sql_ready)
        throw rbx::error("SQL management is not ready");

    rbx::title("Starting SQL driver ".SQL_DRIVER);


    $myks_parser          = new myks_parser(self::$myks_config);
    cli::box("Scanning : mykses paths", '● '.join(LF.'● ', $myks_parser->myks_paths));

    $types_xml            = $myks_parser->out("mykse");
    $tables_xml           = $myks_parser->out("table");

    $this->tables_xml_tdy = simplexml_import_dom(myks::tables_reflection($tables_xml));
    $this->types_xml      = simplexml_import_dom($types_xml);
    $this->tables_xml     = simplexml_import_dom($tables_xml, "field");
    $this->procedures_xml = simplexml_import_dom($myks_parser->out("procedure"));
    $this->views_xml      = simplexml_import_dom($myks_parser->out("view"));

    //echo chunk_split($this->tables_xml->asXML(),90);die('here');
    if(!$this->types_xml instanceof SimpleXMLElement) die("Unable to load types_xml");



    $xsl_trans  = RSRCS_PATH."/xsl/metas/myks_tables.xsl";
    $tables_xml = xsl::resolve($tables_xml, $xsl_trans);
    $tables_xml = simplexml_import_dom($tables_xml);

    myks_gen::init($this->types_xml, $tables_xml);

    $this->views_list = $this->dependencies_scanner();

        //there is no need for tables_ghosts_views to be in myks_gen..
    myks_gen::$tables_ghosts_views = array_extract(
        array_map(array('sql', 'resolve'),
            array_keys($this->views_list)),'name'); //for table ghost exclusion

    $this->queries = array();
  }

  function go($run_queries = false){
    $this->queue(false, "start");
    $this->scan_views();
    $this->scan_procedures();
    $this->scan_tables();
    $this->autoinc_sync();

    $this->queue(bool($run_queries), "end");

  }


  private function queries_queue($queries){
    $this->queries = array_merge($this->queries, $queries);
  }

/**
*  Query stack manager
*  We can - prepare a query queue
*/
  private $stack_mode = false;
  private function queue($run_queue, $stack = false){
    if($stack === "start" ) {
        $this->stack_mode = true;
        return;
    }
    if($stack === "end") {
        $this->stack_mode = false;
    }

    if($this->stack_mode)
        return;

    if(!$run_queue && !$this->stack_mode) { //abort
        $this->queries = array();
        return;
    }

    $this->stack_mode = false;
    if(!$this->queries) {
        rbx::ok("-- No queries to execute");
        return;
    }
        //at least, we can run queries 
    cli::trace("-- Running %d queries", count($this->queries));
    array_walk($this->queries, array('sql', 'query_raw'));

    $this->queries = array(); 
    rbx::ok("-- Queries done & reset");
  }

  private function pattern_exlude_filter($filter, $value){
    if(!$filter) return false;
    $filter = "#".str_replace("*", ".*", $filter)."#";
    return !preg_match($filter, $value);
  }


  function scan_views($run_queries = false, $only_stuff = ''){
    myks_gen::reset_types();
    rbx::title("Analysing views");
    $this->dependencies_ordering();


    $deps_views = array();

    foreach($this->views_list as $view_name=>$view_infos){

        $parent_has_been_reloaded_and_so_should_i_be = (bool)array_intersect(
            $view_infos['dependencies'],
            $deps_views
        );
        $view_xml = $view_infos['xml'];
        $name = (string)$view_xml['name'];
        if($this->pattern_exlude_filter($only_stuff, $name)) continue;

        list($res, $cascade) = myks_gen::view_check($view_xml, $parent_has_been_reloaded_and_so_should_i_be);
        if(!$res) {
            rbx::ok("-- Nothing to do in $name");
            continue;
        }
        rbx::ok("-- New definition for $name");
        $this->queries_queue($res);
        echo join(END, $res).END; flush();
        rbx::ok("-- end of definition");

        if($cascade) $deps_views[] = $name; //we worked on that view

    }

    rbx::line();
    $this->queue(bool($run_queries));
  }


  function scan_procedures($run_queries = false, $only_stuff = ''){
    
    myks_gen::reset_types();
    rbx::title("Analysing stored procedures");
    foreach($this->procedures_xml->procedure as $procedure_xml){
        $name = (string)$procedure_xml['name'];
        if($this->pattern_exlude_filter($only_stuff, $name)) continue;

        $res = myks_gen::procedure_check($procedure_xml);
        if(!$res) {
            rbx::ok("-- Nothing to do in $name");
            continue;
        }

        rbx::ok("-- New definition for $name");
        $this->queries_queue($res);
        echo join(END, $res).END; flush();
        rbx::ok("-- end of definition");
    }

    rbx::line();
    $this->queue(bool($run_queries));
 }

  function autoinc_sync(){
    if(SQL_DRIVER != "pgsql") return;
    pgsql_auto_inc_sync::doit($this->tables_xml_tdy, $this->types_xml);
  }


  function scan_tables($run_queries = false, $only_stuff = ''){

    myks_gen::reset_types();
    rbx::title("Analysing database structure");

    foreach($this->tables_xml->table as $table_xml){
        $name=(string)$table_xml['name'];
        if($this->pattern_exlude_filter($only_stuff, $name)) continue;

        $res = myks_gen::table_check($table_xml);
        if(!$res) {
            rbx::ok("-- Nothing to do in $name");
            continue;
        };
        $this->queries_queue($res);
        echo join(END, $res).END; flush();
    }

    rbx::line();
    $this->queue(bool($run_queries));
 }


  private function dependencies_scanner(){
    rbx::ok("Step 1 : scanning dependencies");

    $search     = "#`([a-z0-9_-]+)`#i";
    $views_list = array();

    foreach($this->views_xml->view as $view_xml){
        $view_name = (string)$view_xml['name'];
        $def       = (string) $view_xml->def;

        preg_match_all($search, $def, $out);
        $dependencies = array_unique($out[1]);
        $views_list[$view_name]['name'] = $view_name;
        $views_list[$view_name]['xml'] = $view_xml;
        $views_list[$view_name]['dependencies'] = $dependencies;
    }

    return $views_list;
   }

//sort the list of all views according to internal dependencies (void)
   private function dependencies_ordering(){

    $dependencies = array_filter(array_extract($this->views_list, 'dependencies'));
    if(!$dependencies)
        return;

    foreach($dependencies as &$d) $d = '    ◦'.join(LF.'    ◦', $d);
    cli::box("Dependencies are", mask_join(LF,$dependencies, "● %2\$s\n%1\$s"));
    rbx::ok("Step 2 : building recursive tree");

    $build_list = array();

    foreach($this->views_list as $view_name=>$view_infos){
	$dependencies = $view_infos['dependencies'];
	unset($view_infos['dependencies']);

	$build_list[$view_name]=array_merge(
		$build_list[$view_name]?$build_list[$view_name]:array(),
		$view_infos
	);

	foreach($dependencies as $dependency)
		 $build_list[$view_name]['dependencies'][$dependency]= &$build_list[$dependency];
    }

    rbx::ok("Step 3 : retrieving maximum depths for a script");

    $build_depth = array();
    foreach($build_list as $name=>$infos) self::scan_depth($infos, $build_depth);

    rbx::ok("Step 4 : Reversing depth && re-ordering base list");

    arsort($build_depth);

    $this->views_list = array_sort($this->views_list,array_keys($build_depth));
  }

  private static function scan_depth($infos, &$build_depth, $depth=0,$path=array()){
	$name=$infos['name'];
	if(in_array($name,$path)) return ;
	$build_depth[$name] = max($build_depth[$name],$depth);
	if($infos['dependencies'])
	foreach($infos['dependencies'] as $infos)
		self::scan_depth($infos, $build_depth, $depth+1,array_merge($path,array($name)));
  }


}
