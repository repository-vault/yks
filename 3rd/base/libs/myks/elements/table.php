<?php



abstract class table_base  extends myks_installer {
  protected $sql_type = 'TABLE';

  protected $escape_char="`";

  protected $table_name;
  protected $virtual        = false;

  private $abstract;

  protected $constraints;

  public $comment_xml;

  function get_name(){
    return $this->table_name;
  }


  function delete_def(){
    return array(
        "DROP TABLE {$this->table_name['safe']}"
    );
  }

  function __construct($table_xml){
    $this->xml = $table_xml;
    $this->virtual   = bool($table_xml['virtual']);
    $this->table_name = sql::resolve( (string) $table_xml['name']);



    $this->constraints   = new myks_constraints($this, $table_xml->xpath('constraints/constraint'));

    if($this->xml->abstract) {
        $abstract = $this->xml->abstract;
        if($abstract['type'] == "tree_integral")
            $this->abstract = new tree_integral($this, $abstract);
        else $this->abstract = new materialized_view($this, $abstract);
    }

  }

  public function table_where(){
    return array(
        'table_name'   => $this->table_name['name'],
        'table_schema' => $this->table_name['schema'],
    );
  }


  function alter_def(){
    if($this->virtual) {
        rbx::ok("-- Table is virtual table {$this->table_name['hash']}, skipping");
        return array();
    }

    if(!$this->modified())
        return array();

    $todo = $this->sql ? array() : $this->create();
    if($this->abstract){
        $todo = array_merge($todo, $this->abstract->alter_def());
    }

    return $todo;
  }


  function modified(){
    $modified = $this->comment_raw != $this->comment_new;

    if($this->abstract)
        $modified |= $this->abstract->modified();

    return $modified;
  }


/*
    populate fields_sql_def from the SQL structure
    return (boolean) whereas this table already exists (alter mode) or not (create mode)
*/

  public function sql_infos(){
    $this->sql = sql::row("information_schema.tables", $this->table_where());
        //load comment
   $where = sql::where( array(
        "c.relkind" => "r",
        "c.relname" => $this->table_name['name'],
        "n.nspname" => $this->table_name['schema'],
    ));
    $query = "SELECT
        n.nspname             AS schema_name,
        c.relname             AS table_name,
        pg_get_viewdef(c.oid) AS compiled_definition,
        d.description         AS full_description
      FROM
        pg_class AS c
        LEFT JOIN pg_namespace AS n ON n.oid = c.relnamespace
        LEFT JOIN pg_description AS d ON c.oid = d.objoid
      $where;
    ";
    $this->comment_raw = sql::qvalue($query, 'full_description');
    $this->comment_xml = @simplexml_load_string(XML_VERSION."<comment>{$this->comment_raw}</comment>");

    if(empty($this->sql))
        return;

    if($this->abstract)
        $this->abstract->sql_infos();

  }

  protected function get_comment_new(){
    $str = strip_start($this->comment_xml->asXML(), XML_VERSION);
    $str = preg_reduce("#^<comment>(.*?)</comment>$#e", $str);
    return $str;
  }

  public function save_comment(){
    if($this->comment_raw == $this->comment_new)
      return array(); //ras
    $comment_sql = sprintf("COMMENT ON %s %s IS %s",
            $this->sql_type,
            $this->table_name['safe'],
            sprintf("E'%s'", addslashes($this->comment_new)));
    return array($comment_sql);
 }

/*
    populate fields_xml_def and keys_xml_def definition based on the xml structure
*/

  function xml_infos(){
    if($this->abstract)
        $this->abstract->xml_infos();
  }


  //forward constraints management (from mykse)
  function key_add($type, $field, $refs=array()){
    $this->constraints->key_add($type, $field, $refs);
  }



 public static function build_ref($table_schema, $table_name, $table_fields){
    return compact('table_schema', 'table_name', 'table_fields');
 }

 public static function output_ref($ref){
    return  sprintf('"%s"."%s"(%s)',
        $ref['table_schema'],
        $ref['table_name'],
        join(',',$ref['table_fields']) );
 }

}

