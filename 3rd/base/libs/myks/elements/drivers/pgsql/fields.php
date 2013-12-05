<?php

class fields {

  private  $fields_xml;

  private $parent;

  private $sql_def = array();
  private $xml_def = array();

  function __construct($parent, $fields_xml){
    $this->parent     = $parent;
    $this->fields_xml = $fields_xml;
  }

  function sql_infos(){
    sql::select("zks_information_schema_columns", $this->parent->table_where());
    $columns = sql::brute_fetch('column_name'); $table_cols=array();

    foreach($columns as $column_name=>$column){

        $this->sql_def[$column_name] = array(
            'Extra'     => '',
            'Default'   => $column['column_default'],
            'Field'     => $column['column_name'],
            'Type'      => $column['data_type'],
            'Null'      => bool($column['is_nullable']),
        );
    }
  }

  function xml_infos(){
    foreach($this->fields_xml->field as $field_xml){
        $mykse=new mykse($field_xml,$this->parent);
        $this->xml_def[$mykse->field_def['Field']] = $mykse->field_def;
    }
  }

  function modified(){
    return $this->sql_def != $this->xml_def;
  }

  function alter_def(){
    $ec = $this->escape_char;

    $drop_columns = array();

    $table_name = $this->parent->get_name();

    $table_alter = "ALTER TABLE {$table_name['safe']} ";
    $todo = array();
    //fields sync
    foreach($this->xml_def as $field_name=>$field_xml){
        $field_sql = $this->sql_def[$field_name];
        if($field_sql){
            unset($this->sql_def[$field_name]);
            if($field_sql==$field_xml) continue;

            $diff = array_diff_assoc($field_xml,$field_sql);
            foreach($diff as $diff_type=>$new_value){
                if($diff_type=="Null"){
                    if(!$new_value && !is_null($field_xml['Default']) && sql::row($table_name['raw'], array($field_name=>null))  )
                        $todo[] = "UPDATE {$table_name['safe']} "
                            ."SET {$ec}$field_name{$ec}={$field_xml['Default']} WHERE {$ec}$field_name{$ec} IS NULL";
                    $todo[] = "$table_alter ALTER COLUMN {$ec}$field_name{$ec} "
                              .($new_value?"DROP NOT NULL":"SET NOT NULL");
                } elseif($diff_type == "Type"){
                    $todo[] = "$table_alter ALTER COLUMN {$ec}$field_name{$ec} TYPE $new_value";
                    $drop_columns[] = $field_name;
                } elseif($diff_type == "Default"){
                    $value="SET DEFAULT $new_value";
                    if(is_null($new_value))$value="DROP DEFAULT";
                    $todo[] = "$table_alter ALTER COLUMN {$ec}$field_name{$ec} $value";
                } else { rbx::error("-- UNKNOW type of diff : $diff_type"); }
            }
        } else { //ajout de colonne
            $todo[] = "$table_alter ADD COLUMN {$ec}$field_name{$ec} {$field_xml['Type']}";
            if(!is_null($field_xml['Default'])){
                $todo[] = "$table_alter ALTER COLUMN {$ec}$field_name{$ec} "
                          ." SET DEFAULT {$field_xml['Default']}";
                $todo[] = "UPDATE {$table_name['safe']} SET {$ec}$field_name{$ec}={$field_xml['Default']}";
            }
            $todo[] = "$table_alter ALTER COLUMN {$ec}$field_name{$ec} "
                .($field_xml['Null']?"DROP NOT NULL":"SET NOT NULL");
        }

    }

    foreach(array_keys($this->sql_def) as $field_name) {
        $todo[]="$table_alter DROP {$ec}$field_name{$ec}";
        $drop_columns[] = $field_name;
    }

    $drop_views = $this->parent->drop_views_from_altered_columns($drop_columns);
    $todo = array_merge( $drop_views, $todo);
    return $todo;
  }
}
