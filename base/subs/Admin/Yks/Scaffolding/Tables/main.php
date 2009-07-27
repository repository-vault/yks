<?

function keys($table){
    $res=array();
    if($table->field) foreach($table->field as $test)
        if($test["key"]=="primary") $res[]= (string)($test['type']?$test['type']:$test);
    return $res;
}

$unique_scaffold_key = substr(md5(join('-', $args)),0,10);

$unique_scaffold_key_list = "{$unique_scaffold_key}_list";

tpls::export(compact('unique_scaffold_key_list'));



$table_name = array_shift($args);


$initial_criteria = array_filter($args);
if(!$initial_criteria) $initial_criteria= array("true");
else {
    parse_str(join("&", $initial_criteria), $initial_criteria);
    $initial_criteria = input_deep($initial_criteria);
}

$table_xml = yks::$get->tables_xml->$table_name;
if(!$table_xml)
    throw rbx::error("Invalid table name");


$table_keys    = keys($table_xml);
$table_fields  = fields($table_xml);

    //ordering table_fields and put primary_keys at first
$table_fields = array_sort($table_fields,
    array_unique(array_merge($table_keys, array_keys($table_fields))));


$multi_depth_criteria = (bool)array_filter((array) $initial_criteria, 'is_array');

