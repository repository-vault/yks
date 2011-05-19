<?
/*
    Manualy check data integrity
*/



class sql_integrity {

  function __construct(){

  }
  //permet de formater un tuple de recherche pour une destruction des elements 
  // e.g : user_tree : (user_id, parent_id)
  private static function tuplize($v, $s="'"){
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




  function check_sql(){
    $verif_fk = array('constraint_type' => 'FOREIGN KEY');
    $cols = "constraint_catalog, constraint_schema, constraint_name, table_schema, table_name";

    $restrict = ""; //$restrict =  "LIMIT 2";
    sql::select("information_schema.table_constraints", $verif_fk, $cols, $restrict);
    $constraints_list = sql::brute_fetch('constraint_name');


    $verif_fks = array('constraint_name' => array_keys($constraints_list));

    $order = (SQL_DRIVER=="pgsql") ? "ORDER BY position_in_unique_constraint ASC" : "";
    sql::select("information_schema.key_column_usage", $verif_fks, "constraint_name,column_name", $order);
    while($l=sql::fetch())
        $constraints_list[$l['constraint_name']]['members'][$l['column_name']]=$l['column_name'];


    sql::select("information_schema.constraint_column_usage", $verif_fks );
    while($l=sql::fetch()) {
        $constraints_list[$l['constraint_name']]['definition']['table_schema'] = $l['table_schema'];
        $constraints_list[$l['constraint_name']]['definition']['table_name']   = $l['table_name'];
        $constraints_list[$l['constraint_name']]['definition']['columns'][] = $l['column_name'];
    }

    
    foreach($constraints_list as $constraint_name=>$constraint)
      self::clean_fk($constraint);

  }

  private static function clean_fk($constraint){
    $constraint_name = $constraint['constraint_name'];
    rbx::ok("Checking $constraint_name");

    $src = $constraint['definition'];
    $src_name = sql::resolve("`{$src['table_schema']}`.`{$src['table_name']}`");

    $dst_name = sql::resolve("`{$constraint['table_schema']}`.`{$constraint['table_name']}`");


    $cols = self::tuplize($src['columns'], "`"); //sql::quote
    $query_src = "SELECT $cols FROM {$src_name['safe']}";

    $query = "SELECT COUNT(*) FROM {$dst_name['safe']}";
    $query .= " WHERE ".self::tuplize($constraint['members'],'`')." NOT IN ($query_src) ";

    $errors = sql::qvalue($query);
    if(!$errors){
      rbx::ok("No integrity errors on $constraint_name");
      return;
    }

    rbx::error("Found $errors integrity errors on $constraint_name");
    cli::pause();
  }


}
