<?


class _mykse extends _sql_base {
  function __construct($from){
    $manager = $this->manager;
    parent::__construct($from);
  }

  function get_fields(){
    $table_name  = $this->sql_table; 
    return $this->fields = fields(yks::$get->tables_xml->$table_name);
  }

  function format_output(){
    $out=array();
    foreach($this->fields as $field_name=>$field_type)
        $out[$field_name] = mykses::value($field_type, $this->$field_name);
    return $out;

  }
}
