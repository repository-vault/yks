<?php


class table_sync {
  private $table_keys;
  private $table_fields;
  private $table_name;

  private $update = array();
  private $create = array();
  public $recreate_all = false;
  private $headers_done = 0;
  
  function __construct($table_name){
    $table_xml = yks::$get->tables_xml->$table_name;
    $table_keys = array_keys(fields($table_xml, "primary"));
    $table_fields = array_keys(fields($table_xml));

    if((!$table_keys) || (!$table_fields))
        throw rbx::error("Unable to find keys or fields for $table_name");

    $this->table_name = $table_name;
    $this->table_keys = $table_keys;
    $this->table_fields = $table_fields;
  }

  function fill(){
    sql::select($this->table_name);
    while($l=sql::fetch()) $this->feed($l);
  }

  function feed($data){
    $keys = array_intersect_key($data, array_flip($this->table_keys));
    $data = array_sort($data, $this->table_fields); // it's important to order fields perfectly

    $create = array_map(array('sql','vals'), $this->recreate_all?$data:$keys);
    $data = array_diff_key($data, array_flip($this->table_keys));

    $this->create[] = mask_join(', ', $create, ($this->headers_done++)?"%s":"%s AS %s");
    $this->update[] = "UPDATE `$this->table_name` ".sql::format($data).' '.sql::where($keys).";";

  }

  function asSQL(){
    $header_keys   = '"'.join('","', $this->table_keys).'"';
    $header_fields = '"'.join('","', $this->table_fields).'"';
        //when recreate_all, we insert ALL columns, else, just keys
    $headers_cols = $this->recreate_all?$header_fields:$header_keys;

    $create = "INSERT INTO `$this->table_name` ($headers_cols) ";
    $data_list = "SELECT ".join(' UNION SELECT ', $this->create);

    if($this->recreate_all) {
        $create .= "SELECT * FROM ($data_list) AS tmp "
                ."  WHERE ($header_keys) NOT IN (SELECT $header_keys FROM `$this->table_name`);";
    } else {
        $create .= $data_list." EXCEPT SELECT $header_keys FROM `$this->table_name`;";
    }

    $updates = join(CRLF, $this->update);
    $query_str = "";
    $query_str.= "-- ".str_pad(" $this->table_name sync ", 67, "-", STR_PAD_BOTH).CRLF;
    $query_str.= $create.CRLF;
    $query_str.= "-- ".str_pad(" $this->table_name data ", 67, "-", STR_PAD_BOTH).CRLF;
    $query_str.= $updates;
    $query_str = sql::unfix($query_str);
    return $query_str;
  }

}