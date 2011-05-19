<?
/*
    Manualy check data integrity
*/



class sql_integrity {

  function __construct(){
    self::check();
  }
  //permet de formater un tuple de recherche pour une destruction des elements 
  // e.g : user_tree : (user_id, parent_id)
  function tuplize($v, $s="'"){
    return count($v)>1 ? "($s".join("$s, $s",$v)."$s)" : "$s".reset($v)."$s";
  }

  public static function check(){
    foreach(yks::$get->types_xml->xpath("/myks/*[@birth]") as $myks_type)
      self::clean($myks_type);
  }

  private static function clean($mykse){
    sql::$queries = array();
    $birth_table = (string)$mykse['birth'];
    $myks_type   = (string)$mykse->getName();


    $birth_xml = yks::$get->tables_xml->$birth_table;
    if(!$birth_xml)
        throw rbx::error("Unable to find element's birth table");

    $tables_where = array();
    foreach(yks::$get->tables_xml as $table_name=>$table_fields){
        if($table_name == $birth_table) continue; //lol

        foreach(fields($table_fields) as $field_name => $field_type){
            if($field_type == $myks_type)
                $tables_where[$table_name][]=(string) $field_name;
        }
    }

    if(!$tables_where) {
      rbx::ok("No references for $myks_type, skipping");
      return;
    } rbx::ok("Checking references for $myks_type");

      //fetch all values
    sql::select($birth_table, true, $myks_type);
    $valid_list = sql::fetch_all();
    
    
    $todo= 0;

    $queries = array();
    foreach($tables_where as $table_name => $fields){
        $where = array();
        foreach($fields as $field) 
            $where[] = sql::in_join($field, $valid_list, 'NOT');
        $where = sql::where($where, $table_name, 'OR');
        sql::select($table_name, $where, join(",", $fields) );
        $res = sql::brute_fetch();
        $todo += $current = count($res);

        if(!$res) continue;

        $uname = sql::resolve($table_name);
        $query = "DELETE FROM {$uname['safe']} WHERE ";
        $res_str = join(',', array_map( array(__CLASS__, "tuplize"), $res));
        $query .= self::tuplize($fields,'`')." IN ($res_str) ";
        $query .= "LIMIT $current";
        $query .= ";";
        $queries[] = $query;
    }

    if(!$queries) 
        rbx::ok("-- Nothing to do for element $myks_type");
    else {
        rbx::title("Element $myks_type : $todo deletion(s)");
        echo join(CRLF, $queries).CRLF;
        rbx::line();
        cli::pause("Press any key to continue");
    }
  }






}
