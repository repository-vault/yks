<?
/*
Les rules ne sont pas verifiées directement, mais via la md5 du ghost complété dans la definition de la vue
_rules deviendra rules_list quand il sera complet ( instead)
*/

class view {
  public $sql_def = array();
  public $xml_def = array();

  private $_rules = array();
  private static $definition_mask = '';
  private static $definition_mask_str = '';
  private static $tmp_view_name='';
  const rule_nothing = 'NOTHING;';

  function __construct($view_xml){
    $view_name = (string) $view_xml['name'];
    $this->view_xml = $view_xml;
    $this->view_name = $view_name;
    $this->view_uname = sql::unquote($view_name);

    $pad = str_repeat("=", 10); $crlf = "[\r\n]+";
    self::$definition_mask = "#$pad <definition\s+signature='([a-f0-9]+)'> $pad$crlf(.*?)$crlf$pad </definition> $pad$crlf?#s";
    self::$definition_mask_str = "$pad <definition signature='%2\$s'> $pad\r\n%1\$s\r\n$pad </definition> $pad\r\n";
  }

  function check(){
    $this->xml_infos();
    $this->sql_infos();
    $same=$this->sql_def == $this->xml_def;

    if($same) return false; //nothing to do

    //print_r(array_keys(array_diff($this->sql_def,  $this->xml_def)));   print_r($this->xml_def);print_r($this->sql_def);die;
    $signature = $this->current_signature();

    $queries = array();
    if($this->sql_def['name'])
        $queries[] = "DROP VIEW \"public\".\"{$this->view_uname}\" CASCADE;";
    
    $queries = array_merge($queries, $this->build_view($this->view_uname));

    $queries[] = "COMMENT ON VIEW \"public\".\"{$this->view_uname}\" IS ".CRLF
        . "E'".addslashes(sprintf(self::$definition_mask_str, $this->xml_def['def'], $signature))."';";

    return sql::unfix(join(CRLF.CRLF, $queries)).CRLF;
  }


    // Retourne la liste des requetes à effectuer pour creer la vue courante 
    // la premiere query retournée DOIT être CREATE VIEW // see current_signature shift
  function build_view($view_name) {
    $queries = array();
    $queries[]= "CREATE OR REPLACE VIEW  \"public\".\"$view_name\" AS ".CRLF
         . $this->xml_def['def'];

    foreach($this->_rules as $rule_name=>$rule_infos){
        $event = strtoupper($rule_infos['event']);
        $where = $rule_infos['where']?"WHERE {$rule_infos['where']}":'';
        $definition = (string) $rule_infos['definition'];
        if(!$definition) $definition = self::rule_nothing;
        $format = $definition==self::rule_nothing ? "%s":"(%s)";
        $definition = sprintf($format, $definition);
        $queries[]= "CREATE RULE \"$rule_name\" AS
            ON $event
            TO \"$view_name\"
            $where
            DO INSTEAD $definition;";
    }

    return $queries;
  }

  function current_signature(){
    $view_name = "ks_tmp_view_".crpt(_NOW,__CLASS__,5);
        //creating ghost temporary view

    if(!sql::query($this->xml_def['def']))
        throw rbx::error("-- Unable to check signature for $this->view_name");
    
    foreach($this->build_view($view_name) as $query)
        sql::query($query);

        //retrieve signature from ghost
    $infos = self::get_sql_infos($view_name, $this->xml_def['def'], $this->view_uname,$this->_rules);

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
    $row = self::get_sql_infos($this->view_uname,false,false,$this->_rules);

    $this->sql_def = array(
        'name'=>$row['viewname'],
        'def'=> $row['base_definition'],
        'signature'=> $row['base_signature'] == $row['signature'],
        'rules'=> $row['rules'],
    );
  }

  private static function get_sql_infos($view_name, $alternative_description=false, $original_name = false,$rules=array()){
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

    foreach($rules as $rule_name=>$rule_infos)
        $row['definition'].= $rule_infos['definition'].$rule_infos['event'];


    $query = "SELECT
        n.nspname AS schemaname,
        c.relname AS viewname,
        r.rulename as rulename,
        pg_get_ruledef(r.oid) AS definition
      FROM 
        pg_rewrite AS r
        LEFT JOIN pg_class AS c ON c.oid = r.ev_class
        LEFT JOIN pg_namespace AS n ON n.oid = c.relnamespace
      WHERE (r.rulename <> '_RETURN' AND c.relname='$view_name')
      ORDER BY r.rulename
    "; sql::query($query); $rules = sql::brute_fetch('rulename', 'definition');


    foreach($rules as $rule_name=>$rule_definition)
        $row['definition'].= $rule_definition;

    $row['rules'] = count($rules);
    $row['signature'] = self::calc_signature($view_name, $row, $original_name);
    return $row;
  }

  function xml_infos() {

    $this->_rules = array();
    foreach($this->view_xml->rule as $rule) {
        $event = (string)$rule['on'];
        $where = (string)$rule['where'];
        $definition  = myks_gen::sql_clean_def($rule);
        $hash = substr(md5($event.$definition),0,5);
        $name = "{$this->view_uname}_{$event}_{$hash}";
        $this->_rules[$name] = compact('name', 'definition', 'event', 'where'); //instead, where...
    }

    $this->xml_def = array(
        'name'=>$this->view_uname,
        'def'=> sql::unfix(myks_gen::sql_clean_def($this->view_xml->def)),
        'signature'=>true,
        'rules'=>count($this->_rules),
    );
  }
}
