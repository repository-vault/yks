<?php



class sql_runner {

  private $types_xml;
  private $tables_xml;

  private $procedures_xml;
  private $views_xml;

  private static $myks_config;
  private static $sql_ready = false;


  private static $blind_mode = false;// dont look in database

  static function init(){
    if(class_exists('classes') && !classes::init_need(__CLASS__)) return;

    include_once myks::$LIBS."/elements.php";
    include_once myks::$LIBS."/generator.php";

    classes::register_class_path("pgsql_auto_inc_sync",
        myks::$LIBS."/elements/drivers/pgsql/misc/auto_inc_sync.php");

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
    //$this->dblink("ivs_distributor_items_assoc", "distributor_id", "product_serial");die;
    //$this->scan_tables("sessions_results_heap");
    //$this->scan_procedures("spli", true);
    //$this->scan_views("ttriggers", true);
  }

  /**
   *  @alias reinit
   */
  function init_engine(){
    if(!self::$sql_ready)
      throw rbx::error("SQL management is not ready");

    rbx::title("Starting SQL driver ".SQL_DRIVER);


    $myks_parser = myks::get_parser();
    $myks_parser->trace();

    $types_xml            = $myks_parser->out("mykse");

    $tables_xml           = $myks_parser->out("table");

    $this->tables_xml_tdy = simplexml_import_dom(myks::tables_reflection($tables_xml));
    $this->types_xml      = simplexml_import_dom($types_xml);
    $this->tables_xml     = simplexml_import_dom($tables_xml);

    $this->procedures_xml = simplexml_import_dom($myks_parser->out("procedure"));
    $this->views_xml      = simplexml_import_dom($myks_parser->out("view"));

    $this->last_execution_count = 0;

    //echo chunk_split($this->tables_xml->asXML(),90);die('here');
    if(!$this->types_xml instanceof SimpleXMLElement) die("Unable to load types_xml");



    $xsl_trans  = RSRCS_PATH."/xsl/metas/myks_tables.xsl";
    $tables_xml = xsl::resolve($tables_xml, $xsl_trans);
    $tables_xml = simplexml_import_dom($tables_xml);

    myks_gen::init($this->types_xml, $tables_xml);

    $this->views_list = $this->dependencies_scanner();

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

  function integrity(){
    interactive_runner::start("sql_integrity");
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
    $this->last_execution_count = 0;

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
    $this->last_execution_count = count($this->queries);
    cli::trace("-- Running %d queries", $this->last_execution_count );
    array_walk($this->queries, array('sql', 'query_raw'));

    $this->queries = array();
    rbx::ok("-- Queries done & reset");
  }

  private function pattern_exlude_filter($filter, $value){
    if(!$filter) return false;
    $filter = "#".str_replace("*", ".*", $filter)."#";
    return !preg_match($filter, $value);
  }

  /**
   * @alias v * true
   */
  function scan_views($only_stuff = '*', $run_queries = false){
    rbx::title("Analysing views");
    $this->dependencies_ordering();

    $deps_views = array();

    foreach($this->views_list as $view_name=>$view_infos) {
      $parent_has_been_reloaded_and_so_should_i_be = (bool) array_intersect(
        $view_infos['dependencies'],
        $deps_views
      );
      $forced  = self::$blind_mode || $parent_has_been_reloaded_and_so_should_i_be;

      $view_xml = $view_infos['xml'];
      $name = (string)$view_xml['name'];
      if($this->pattern_exlude_filter($only_stuff, $name)) continue;

      list($res, $cascade) = myks_gen::view_check($view_xml, $forced);

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

  /**
   * @alias p * true
   */
  function scan_procedures($only_stuff = '*', $run_queries = false) {
    rbx::title("Analysing stored procedures");
    foreach($this->procedures_xml->procedure as $procedure_xml){
      $name = (string)$procedure_xml['name'];
      if($this->pattern_exlude_filter($only_stuff, $name)) continue;

      $res = myks_gen::procedure_check($procedure_xml, self::$blind_mode);
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


  function init_database($run_queries = false){
    self::$blind_mode = true;

    sql::query("CREATE LANGUAGE 'plpgsql' HANDLER plpgsql_call_handler LANCOMPILER 'PL/pgSQL'");
    $this->scan_procedures("*", $run_queries);


    sql::query('CREATE AGGREGATE "concat_comma"(BASETYPE="text",SFUNC="concat_comma",STYPE="text")');
    sql::query('CREATE AGGREGATE first(int)(sfunc=coalesce_first, stype = int)');
    sql::query('CREATE AGGREGATE first(text)(sfunc=coalesce_first, stype = text)');
    sql::query('CREATE AGGREGATE first(varchar)(sfunc=coalesce_first, stype = varchar)');
    sql::query('CREATE AGGREGATE first(boolean)(sfunc=coalesce_first, stype = boolean )');



    $this->scan_views("zks", $run_queries);

    self::$blind_mode = false;
    $this->scan_views("zks", $run_queries);
    $this->scan_views("zks", $run_queries);
  }

  /**
  * @alias t * true
  */
  function scan_tables($only_stuff = '*', $run_queries = false){

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
    if($infos['dependencies']) {
      foreach($infos['dependencies'] as $infos) {
        self::scan_depth($infos, $build_depth, $depth+1,array_merge($path,array($name)));
      }
    }
  }




  function dblink_bind(){
    $current_db = yks::$get->config->sql->links->db_link;

    foreach(yks::$get->config->sql->dblink->iterate("remote_dsn") as $dsn) {
      $dsn_str = array();
      $dsn_str["dbname"] = $dsn['db'];
      if($dsn['host']) $dsn_str["host"] = $dsn['host'];
      if($dsn['user']) $dsn_str["user"] = $dsn['user'];
      if($dsn['pass']) $dsn_str["password"] = $dsn['pass'];
      if($dsn['port']) $dsn_str["port"] = $dsn['port'];
      $dsn_str = mask_join(' ', $dsn_str, '%2$s=%1$s');
      $dsn_ns  = $dsn['ns'];
      $data = array(
        'db_name'  => $current_db['db'],
        'dsn_str'  => $dsn_str,
      );
      sql::replace("zks_dblink_dsn", $data , compact('dsn_ns')  );
      rbx::ok("Registered link : $dsn_ns to {$data['dsn_str']}");
    }
  }



  function dblink($table_name){

    $site_code = yks::$get->config->sql->dblink['local_ns'];

    if(!$site_code)
      throw new Exception("Please specify a config/sql/dblink/@local_ns directive");

    $args = func_get_args();
    $args = array_slice($args,1);

    $table_name = sql::resolve( $table_name );

    $table_columns = array();

    $verif_table = array(
      'table_schema' => $table_name['schema'],
      'table_name'   => $table_name['name']
    ); sql::select("zks_information_schema_columns", $verif_table);
    $sql_columns = sql::brute_fetch('column_name', 'data_type');

      //for views, we use information schema reflection, for table, xml definition
    if($this->views_list[$table_name['raw']]) {
      $table_columns = $sql_columns;
    } else {

      $table_xml = first($this->tables_xml->xpath("table[@name='{$table_name['raw']}']"));
      if(!$table_xml) {
          //last chance for unmapped tables
        if($sql_columns)
          $table_columns = $sql_columns;
        else
          throw rbx::error("Cannot resolve {$table_name['raw']}");
      } else {
        foreach($table_xml->fields->field as $field_xml){
          $field_name = pick((string)$field_xml['name'], (string)$field_xml['type']);
          $mykse      = new mykse($field_xml);
          $field_type = (string) $mykse->field_def['Type'];
          $table_columns [$field_name] = $field_type;
        }
      }
    }
    $table_rcolumns = array(); $i=0;
    foreach($table_columns as $field_name=>$field_type)
        $table_rcolumns[pick($args[$i++], $field_name)] = $field_type;

    $table_fields = array_combine(array_keys($table_columns), array_keys($table_rcolumns));

    $query = "E'SELECT ".mask_join(', ', $table_fields, '%2$s')." FROM {$table_name['safe']}'";
    $select_str = "SELECT " . mask_join(', ', $table_fields, '%s')
            . " FROM dblink(dblink_dsn('$site_code'), $query)"
            . " AS {$table_name['name']} (".mask_join(', ', $table_rcolumns, '%2$s %1$s').")". ";";

    $new_v = " || IF(NEW.%1\$s IS NULL, 'NULL', E'\\'' || NEW.%1\$s || E'\\'' ) || ";
    $new_vmask = "%2\$s = '$new_v'";


    $old_v = " || IF(OLD.%1\$s IS NULL, 'IS NULL', E' = \\'' || OLD.%1\$s || E'\\'' ) || ";
    $old_vmask = "%2\$s '$old_v'";


    $insert_str = "E'INSERT INTO {$table_name['safe']} "
            . "(" . mask_join(', ', $table_fields, '%2$s').")"
            . " VALUES "
            . "('" . mask_join("', '", $table_fields, $new_v)."');'";


    $delete_str = "E'DELETE FROM {$table_name['safe']} WHERE ".mask_join(" AND ", $table_fields, $old_vmask)." ;'";
    $update_str = "E'UPDATE {$table_name['safe']} SET ".mask_join(',', $table_fields, $new_vmask)
        . " WHERE ".mask_join(" AND ", $table_fields, $old_vmask)." ;'";

    $view  = "<view name=\"{$table_name['name']}\">".CRLF;
    $view .= "<def>$select_str</def>".CRLF;
    $view .= "<rule on='insert'>" . "SELECT dblink_exec(dblink_dsn('$site_code'), $insert_str)". "</rule>".CRLF;
    $view .= "<rule on='delete'>" . "SELECT dblink_exec(dblink_dsn('$site_code'), $delete_str)". "</rule>".CRLF;
    $view .= "<rule on='update'>" . "SELECT dblink_exec(dblink_dsn('$site_code'), $update_str)". "</rule>".CRLF;
    $view .= "</view>".CRLF;
    rbx::line();
    echo $view;
    rbx::line();
  }


  function expose($myks_type){
    $sub_sql = mykses::build_find_query($myks_type);
    rbx::line();
    echo $sub_sql.CRLF;
    rbx::line();
  }

/**
* Find the usage of a native type for a given value
* @alias find
*/
  function find_key($myks_type, $value){
    return mykses::dump_key($myks_type, $value);
  }
}
