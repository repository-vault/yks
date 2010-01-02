<?
/*
    Manualy check data integrity
    only do basic "deletecascade", i should support "set_null" action
*/

if(SQL_DRIVER!= "mysqli") 
    throw rbx::error("You DONT want to do that");


$types_xml = yks::$get->types_xml;
$parent_types = $types_xml->xpath("/myks/*[@birth]");
foreach($parent_types as &$type) $type=$type->getName();

$myks_types  = $parent_types;


function clean($myks_type){
    sql::$queries=array();

    $types_xml = yks::$get->types_xml;
    $tables_xml = yks::$get->tables_xml;


    $mykse = $types_xml->$myks_type;
    $birth_table = (string)$mykse['birth'];
    $birth_xml = $tables_xml->$birth_table;
    if(!$birth_xml)
        throw rbx::error("Unable to find element's birth table");

    $tables_where =array();
    foreach($tables_xml as $table_name=>$table_fields){
        if($table_name == $birth_table) continue;

        foreach($table_fields->field as $field){
            $field_type = (string) ($field['type']?$field['type']:$field);
            if($field_type == $myks_type)
                $tables_where[$table_name][]=(string) $field;
        }
    }

    sql::select($birth_table, true, $myks_type);
    $valid_list = sql::brute_fetch(false, $myks_type);
    
    $todo= 0;

    $queries=array();

    foreach($tables_where as $table_name=>$fields){
        $where=array();
        foreach($fields as $field) 
            $where[] =sql::in_join($field,$valid_list, 'NOT');
        $where = sql::where($where, $table_name, '||');
        sql::select($table_name, $where, join(",", $fields) );
        $res = array_map( "tuplize", sql::brute_fetch());
        $todo += $current = count($res);

        if(!$res) continue;

        $uname = sql::unquote($table_name);
        $query = "DELETE FROM $uname WHERE ";
        $query .= tuplize($fields,'`')." IN (". join(',', $res).") ";
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
    }

}

function tuplize($v, $s="'"){ return count($v)>1?"($s".join("$s, $s",$v)."$s)":"$s".reset($v)."$s"; }

foreach($myks_types as $myks_type)
    clean($myks_type);


die;