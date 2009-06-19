<?



abstract class view_base {
  public $sql_def = array();
  public $xml_def = array();


  private $update_cascade = false;

  private static $definition_mask = '';
  private static $definition_mask_str = '';
  private static $tmp_view_name='';

  function __construct($view_xml){
    $view_name = (string) $view_xml['name'];
    $this->view_xml = $view_xml;
    $this->view_name = $view_name;
    $this->name = $this->view_uname = sql::unquote($view_name);

    $pad = str_repeat("=", 10); $crlf = "[\r\n]+";
    self::$definition_mask = "#$pad <definition\s+signature='([a-f0-9]+)'> $pad$crlf(.*?)$crlf$pad </definition> $pad$crlf?#s";
    self::$definition_mask_str = "$pad <definition signature='%2\$s'> $pad\r\n%1\$s\r\n$pad </definition> $pad\r\n";
  }

  function check($force = false){
    $this->xml_infos();


    if(!$force) $this->sql_infos();

    if(!$this->modified())  return false;
    $todo = $this->update();

    //print_r(array_show_diff($this->sql_def,  $this->xml_def, 'sql', 'xml'));die;

    if(!$todo)
        throw rbx::error("-- Unable to look for differences in $$this->view_uname");
    $todo = array_map(array('sql', 'unfix'), $todo);

    return array($todo, $this->update_cascade);
  }

  function modified(){
    return $this->sql_def != $this->xml_def;
  }

  function update(){
    return $this->alter_def();
  }

  function alter_def($force = false){
    $todo = array();
    if(!$force && ($this->sql_def == $this->xml_def)) return $todo;
    $this->update_cascade = true;
    if($force || $this->sql_def['name'])
        $todo[] = "DROP VIEW IF EXISTS \"public\".\"{$this->view_uname}\" CASCADE";

    $signature = $this->current_signature();
    $todo []= "CREATE OR REPLACE VIEW  \"public\".\"$this->view_uname\" AS ".CRLF
         . $this->xml_def['def'];

    $todo[] = "COMMENT ON VIEW \"public\".\"{$this->view_uname}\" IS ".CRLF
        . "E'".addslashes(sprintf(self::$definition_mask_str, $this->xml_def['def'], $signature))."'";
    return $todo;
  }


  function current_signature(){
    $view_name = "ks_tmp_view_".crpt(_NOW,__CLASS__,5);
        //creating ghost temporary view

    if(!sql::query(rtrim($this->xml_def['def'],";")." LIMIT 1"))
        throw rbx::error("-- Unable to check signature for $this->view_name");
    
    sql::query($query = "CREATE OR REPLACE VIEW  \"public\".\"$view_name\" AS ".CRLF
         . $this->xml_def['def']);

        //retrieve signature from ghost
    $infos = self::get_sql_infos($view_name, $this->xml_def['def'], $this->view_uname);

        //kill ghost
    sql::query("DROP VIEW \"$view_name\" ");

    if(!$infos['definition']) return; //TODO
    return  $infos['signature'];
  }

  static function calc_signature($view_name, $view_infos, $base_name){
    $definition = $view_infos['definition'];
    $definition = str_replace($view_name, $base_name, $definition);
    return crpt($definition.$view_infos['base_definition'],'FLAG_SQL',8);
  }

  function sql_infos(){
    $row = self::get_sql_infos($this->view_uname, false, false);
   
    $this->sql_def = array(
        'name'=>$row['viewname'],
        'def'=> $row['base_definition'],
        'signature'=> $row['base_signature'] == $row['signature'],
    );
  }

  private static function get_sql_infos($view_name, $alternative_description=false, $original_name = false){
    if(!$original_name) $original_name = $view_name;

    $query = "SELECT
        n.nspname AS schemaname,
        c.relname AS viewname,
        pg_get_viewdef(c.oid) AS definition,
        d.description as description
      FROM 
        pg_class AS c
        LEFT JOIN pg_namespace AS n ON n.oid = c.relnamespace
        LEFT JOIN pg_description AS d ON c.relfilenode = d.objoid
      WHERE (c.relkind = 'v' AND c.relname='$view_name' );
    "; $row = sql::qrow($query);


    if($alternative_description)
        $row['base_definition'] =  $alternative_description;
    else {
        preg_match(self::$definition_mask, $row['description'], $out);
        $row['base_definition'] = myks_gen::sql_clean_def($out[2]);
        $row['base_signature'] = (string)$out[1];
    }

    $row['signature'] = self::calc_signature($view_name, $row, $original_name);
    return $row;
  }

  function xml_infos() {

    $this->xml_def = array(
        'name'=>$this->view_uname,
        'def'=> myks_gen::sql_clean_def($this->view_xml->def),
        'signature'=>true,
    );
  }
}
