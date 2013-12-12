<?php

class sql_stmt_parser {
  const pad = " \t\n\r";
  const STRING = "(?:\"([^\"]*)\"|'([^']*)'|([a-z0-9-]+))";
  const DEFAULT_SCHEMA = 'main';

  const SQL_CLASS_NAME = 'sql_parsed_table';
  static function parse($sql_str){
    $i = 0;
    return self::parse_create($sql_str, $i);
  }

  private static function parse_create($str, &$i) {
    $i += strspn($str, self::pad, $i);
    $mask = "#^CREATE\s+TABLE#";
    if(!preg_match($mask, substr($str,$i), $out))
      throw new Exception("Invalid table parsing");
    $i += strlen($out[0]);

    $table_name = self::parse_dsn($str, $i);
        $tmp = self::SQL_CLASS_NAME;
    $table = new $tmp($table_name);

    self::parse_fields($str, $i, $table);
    return $table;
  }
  private static function parse_fields($str, &$i, $table){
    $i += strspn($str, self::pad, $i);
    if($str[$i++]!='(')
      throw new Exception("Invalid fields list");
    $fields = array();
    do {
      $field = self::parse_field($str, $i, $table);
      if($field)
          $table->field_add($field);
      if($str[$i]!=',') {
        return $fields;
      }
      $i++;
    } while($str[$i]!=")" && $str[$i] != "");
  }

  private static function parse_field($str,  &$i, $table){
    $i += strspn($str, self::pad, $i);
    $field = array(
      'Field'  => null,
      'Type'   => null,
      'Null'   => 1,
    );

    $mask = array(
      'name' => self::STRING,
      'type' => "INTEGER|BOOL|REAL|DOUBLE|FLOAT|CHAR|TEXT|VARCHAR|BLOB|NUMERIC|DATETIME",
      'key'  => "PRIMARY\s+KEY",
      'null'=> "NOT\s+NULL|NULL",
      //'end'=> ',',
    ); $mask = "#^\s*(?:".mask_join("|", $mask, '(?P<%2$s>%1$s)').")\s*#";

    while(preg_match($mask, substr($str, $i), $out)) {
      $i += strlen($out[0]);
      if($out['name']) $field['Field'] = pick($out[2],$out[3],$out[4]) ;
      if($out['type']) $field['Type'] = $out['type'];
      if($out['null']) $field['Null'] = (int)($out['null']=="NULL");

      if($out['key']) {
          $types=array('PRIMARY KEY'=>'PRIMARY','FOREIGN KEY'=>'FOREIGN','UNIQUE'=>'UNIQUE');
          $type = $types[$out['key']];
          if(!$field['Field']) {//on est sur un couple de clef uniquement
            $members = self::parse_strings($str, $i);
          } else $members = array($field['Field']);
          foreach($members as $tmp)
            $table->key_add($type, $tmp);
        }
    }


    return $field['Field'] ? $field : null;
  }

    //comma separated strings tupple
  private static function parse_strings($str, &$i) {
    $i += strspn($str, self::pad, $i);
    if($str[$i++]!="(")
      throw new Exception("Invalid tupple");
    $vals = array();
    do {
      $val = self::parse_string($str, $i);
      if($str) $vals[] = $val;
    } while($str[$i++]==",");

    if($str[$i-1]!=")")
      throw new Exception("Invalid tupple");
    return $vals;
  }

  private static function parse_dsn($str, &$i){
    $first = self::parse_string($str, $i);
    if($str[$i]=='.') {
      $second = self::parse_string($str, ++$i);
      $table_name = array('schema' => $first, 'name'=>$second);
    } else $table_name = array('schema' => self::DEFAULT_SCHEMA, 'name'=>$first);
    return $table_name;
  }

  private static function parse_string($str, &$i){
    $i += strspn($str, self::pad, $i);
    $mask = "#".self::STRING."#";
    if(!preg_match($mask, substr($str,$i), $out))
      throw new Exception("Invalid string $mask");

    $i += strlen($out[0]);
    $i += strspn($str, self::pad, $i);
    return pick($out[1],$out[2], $out[3]);
  }
}
