<?php

//mykse elements manager

class mykses {

  private static $births = false;

  function __construct_static(){
    self::$births = array();
    foreach(yks::$get->types_xml as $type)
      if((string)$type['birth'])
        self::$births[$type->getName()] = (string)$type['birth'];
  }

  static function vals($type){
    return vals(yks::$get->types_xml->$type);
  }


  static function value($mykse_type, $val){
    static $types_xml = false; if(!$types_xml) $types_xml = yks::$get->types_xml;
    $mykse = $types_xml->$mykse_type;
    if(!$mykse) return $val;
    $mykse_type = $mykse['type'];
    if($mykse_type=='bool') return bool($val,true);
    elseif($mykse_type=='time') return date('d/m/Y',$val);
    elseif($mykse_type == 'text') return specialchars_encode($val);
    elseif(in_array($mykse_type, array('text', 'string','int')) )
        return $val;
    elseif($mykse_type) return self::value($mykse_type, $val);
  }

  static function out($data, $fields=array()){
    $types_xml = yks::$get->types_xml; $out=array();

    foreach($data as $field_name=>$val){
        $mykse_type = isset($fields[$field_name])?$fields[$field_name]:$field_name;
        $out[$field_name] = self::value($mykse_type, $val);
     } return $out;
  }

  static function validate_update($data, $filter_in) {
    return self::validate($data, $filter_in, false);
  }

/*
* @full_validation : force un-spececified params to "null" (or to the default value /type)
*/
  static function validate($data,$filter_in, $full_validatation = true) {

    $types_xml = yks::$get->types_xml;
    $out=array();$filter_unique=false;
    if($filter_in instanceof simpleXmlElement) $filter_in=fields($filter_in);
    if(!is_array($filter_in)){
        $filter_in = array($filter_unique=$filter_in);
        if(!is_array($data)) $data=array($filter_unique=>$data);
    }
    if(!$full_validatation)
        $filter_in = array_intersect_key($filter_in, $data);


    //if(!is_array($data))  DONT cast here since $data might of an array type user_flags[]
    foreach($filter_in as $mykse_key=>$mykse_type){

        if(is_numeric($mykse_key)) $mykse_key = $mykse_type;

        if(!isset($data[$mykse_key]) && !is_null($data[$mykse_key])) continue;
        $val_init = $val = $data[$mykse_key];
        $mykse = $types_xml->$mykse_type;
        $null = is_null($val);
        $mykse_start_type = $mykse_type;

      while(true) {    //loop to recurse

        if(!$mykse) break;
        $mykse_type=(string) $mykse['type'];

        $nullable = $mykse['null']=='null';
        if($null && !$nullable && is_not_null($mykse['default']))break;
        if($null && $nullable){ $out[$mykse_key]=null; break;}

        if(in_array("html", array($mykse_type, $mykse_start_type))){
            $out[$mykse_key] = txt::rte_clean($val);
        }elseif($mykse_type=='bool'){
            $out[$mykse_key] = bool($val,true);
        }elseif($mykse_type=='mail'){
            $val = trim(strtolower($val));
            $out[$mykse_key] = mail_valid($val)?$val:false;
        } elseif(in_array("time", array($mykse_start_type, $mykse_type)) ){
            if($val=="" && $nullable) { $out[$mykse_key]=null; break;}
            if(is_numeric($val)) $out[$mykse_key] = $val;
            else $out[$mykse_key] = pick(
                date::validate($val, DATETIME_MASK),
                date::validate($val, DATE_MASK));
        } elseif(in_array("datesss", array($mykse_start_type, $mykse_type)) ){
            if($val=="" && $nullable) { $out[$mykse_key]=null; break;}
            if(is_numeric($val)) $out[$mykse_key] = $val;
            else $out[$mykse_key] = date::validate($val, DATE_MASK);
        } elseif($mykse_type=='int'){
            if($null) break;
            if($val === "") {$out[$mykse_key] = null;break; }
            $out[$mykse_key]=(int) $val;
        }elseif($mykse_type == 'string' || $mykse_type == 'text'){
            $out[$mykse_key] = $val;
        } elseif($mykse_type=='enum'){
            $vals = vals($mykse);
            if($mykse['set']) {
                if(!is_array($val)) $val=explode(',',$val);
                $val=array_intersect($vals, $val);
                if($val_init && !$val) { $out[$mykse_key]='';break; }
                $out[$mykse_key]=join(',', $val);
            } else {
                $key=array_search($val,$vals);
                if($key===false) { $out[$mykse_key]=null;break; }
                $out[$mykse_key]=$val;
            }
        } elseif($mykse_type){
            $mykse=$types_xml->$mykse_type;
            continue;
        }
        break;
      } //loop

    }
    return $filter_unique?$out[$filter_unique]:$out;
  }


  public static function dump_key($myks_type, $value, $rmap = array(), $recurse = true){
    $tables = self::find_key($myks_type, $value, $rmap, $recurse);

    $delete = array(); $update = array();
    foreach($tables as $table_data){
      $table_name = $table_data[0];
      sql::select($table_name, array($field = $table_data[1] => $table_data[2] ));
      $entries = sql::brute_fetch();

      $mode = $table_data['3'];
      if($mode == 'set_null') {
        foreach($entries as $entry)
          $update[$table_name][] = array('where' => array_merge($entry, array($field => null)), 'data' => array($field => $entry[$field]));
      } else {
        if($delete[$table_name])
            $delete[$table_name] = array_unique_multidimensional(array_merge($delete[$table_name], $entries ));
        else
            $delete[$table_name] = $entries;
      }

    }

    $out = compact('delete', 'update');
    return $out;
  }


  public static function find_key($myks_type, $value, $rmap = array(), $recurse = true, $hpaths = array()){

    if(!is_array($value))
        $value = array($value);
    sort($value);
    $deaths = array_flip(self::$births); //anti-birth...

    $hhash = $myks_type.":".join(',', $value);
    if($hpaths[$hhash])
        return array();
    $hpaths[$hhash] = true;

    $paths = array();

    //Setup env
    $birth_table  = self::$births[$myks_type];
    if(!$birth_table)
      return array();


    $joins = array();
    foreach(data::load("tables_xml") as $table_name => $table_xml){ //main loop
      if(bool($table_xml['virtual']))
        continue;

      $table_fields = fields($table_xml);
      $only_null    = true;
      foreach($table_xml as $field)
        if($field['type'] == $myks_type)
          $only_null &= $field['delete'] == "set_null";

      //Find typed fields
      $fields = array_keys($table_fields, $myks_type);
      if(!$fields)
        continue;

      $cvalues = array();
      foreach($fields as $field_name) {
        sql::select($table_name, array($field_name => $value));
        $cvalues = array_merge($cvalues, sql::brute_fetch());
      }

      if(!$cvalues)
        continue;

      if($only_null){
        foreach($fields as $field_name) {
          $fvalues = array_extract($cvalues, $field_name, true);
          if($fvalues)
            $paths[] = array($table_name, $field_name, $fvalues, 'set_null');
        }
        continue;
      }

      if(($child_type = $deaths[$table_name]) && $recurse && $child_type !=  $myks_type ) {
        $values = array_extract($cvalues, $child_type, true);
        $depth = self::find_key($child_type, $values, $rmap, $recurse, $hpaths);
        $paths   = array_merge($paths, $depth);
      }

      foreach($fields as $field_name) {
        $fvalues = array_extract($cvalues, $field_name, true);
        $fvalues = array_intersect($fvalues, $value);
        if($fvalues)
          $paths[] = array($table_name, $field_name, $fvalues);
      }

            //go to reverse map
      if($parent_columns = $rmap[$table_name]) {
        if(!is_array($parent_columns))
          $parent_columns = array($parent_columns);

          foreach($parent_columns as $parent_column) {
            $parent_type = $table_fields[$parent_column];
            $fvalues = array_extract($cvalues, $parent_column, true);
            $depth = self::find_key($parent_type, $fvalues, $rmap, $recurse, $hpaths);
            $paths   = array_merge($paths, $depth);
          }
      }
    }

    return $paths;
  }

  /*
  * Build a huge query listing all the usage
  * of a native myks type
  */
  public static function build_find_query($myks_type, $ignored_tables = array()){

    //Setup env
    $birth_table  = self::$births[$myks_type];
    if(!$birth_table)
      throw new Exception("Type $myks_type is not a native myks type.");
    $ignored_tables = array_merge(array($birth_table), $ignored_tables);

    $joins = array();
    foreach(yks::$get->tables_xml as $table_name => $table_fields){
      if(in_array($table_name, $ignored_tables))
        continue;

      //Find typed fields
      $fields = array_keys(fields($table_fields), $myks_type);
      if(!$fields)
        continue;

      //Save joins
      foreach($fields as $join_key){
        $from = sql::from($table_name);
        $sql_used = "SELECT DISTINCT $join_key as $myks_type, '$table_name' as table_name, '$join_key' AS mykse_column $from WHERE $join_key IS NOT NULL";
        $joins[] = $sql_used;
      }
    }

    sort($joins);
    //Link joins
    $sql = implode("\n UNION ", $joins);

    return $sql;
  }


}
