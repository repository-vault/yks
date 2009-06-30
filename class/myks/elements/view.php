<?



abstract class view_base extends myks_base {
  public $sql_def = array();
  public $xml_def = array();

  private $update_cascade = false;

  function __construct($view_xml){
    $this->view_xml = $view_xml;
    $this->view_infos     = sql::resolve( (string) $view_xml['name'] );
    $this->view_name      = $this->view_infos['name'];
    $this->view_name_safe = $this->view_infos['safe'];
  }

  function check($force = false){
    $this->sql_infos(); //self signature, sql first
    $this->xml_infos();

    if($force) $this->sql_def = array();
    if(!$this->modified())  return false;
    $todo = $this->update();

    //print_r(array_show_diff($this->sql_def,  $this->xml_def, 'sql', 'xml'));die;
    if(!$todo)
        throw rbx::error("-- Unable to look for differences in $this->view_name");

    $todo = array_map(array('sql', 'unfix'), $todo);
    return array($todo, $this->update_cascade);
  }

  function modified(){
    $res = $this->sql_def != $this->xml_def;
    return $res;
  }

  function update(){
    return $this->alter_def();
  }

  function alter_def($force = false){
    $todo = array();
    if(!$force && ($this->sql_def == $this->xml_def)) return $todo;
    $this->update_cascade = true;
    if($force || $this->sql_def['name'])
        $todo[] = "DROP VIEW IF EXISTS $this->view_name_safe CASCADE";

    $todo []= "CREATE OR REPLACE VIEW  $this->view_name_safe AS ".CRLF
         . $this->xml_def['def'];


    $todo []= $this->sign("VIEW", $this->view_name_safe, $this->xml_def['def'], $this->xml_def['signature'] );
    return $todo;
  }


  protected function calc_signature(){
        //self checked signature
    return $this->crpt(
            $this->sql_def['compiled_definition'],
            $this->xml_def['def']
    );
  }

  function sql_infos(){
   $where = sql::where( array(
        "c.relkind" => "v",
        "c.relname" => $this->view_name,
        "n.nspname" => $this->view_infos['schema']
    ));

    $query = "SELECT
        n.nspname             AS schema_name,
        c.relname             AS view_name,
        pg_get_viewdef(c.oid) AS compiled_definition,
        d.description         AS full_description
      FROM 
        pg_class AS c
        LEFT JOIN pg_namespace AS n ON n.oid = c.relnamespace
        LEFT JOIN pg_description AS d ON c.relfilenode = d.objoid
      $where;
    "; $data = sql::qrow($query);

    $sign = $this->parse_signature_contents($data['full_description']);

    $this->sql_def = array(
        'compiled_definition'=> $data['compiled_definition'],
        'def'=> $sign['base_definition'],
        'signature'=> $sign['signature'],
    );
  }


  function xml_infos() {

    $this->xml_def = array(
        'compiled_definition' => $this->sql_def['compiled_definition'],
        'def'=> myks_gen::sql_clean_def($this->view_xml->def),
     );
    $this->xml_def['signature'] = $this->calc_signature();

  }
}
