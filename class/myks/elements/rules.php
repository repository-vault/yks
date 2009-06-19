<?


abstract class rules_base {
  private $rules_xml = null;
  protected $sql_def = array();
  protected $xml_def = array();
  private $signatures = array();

  const rule_nothing = 'NOTHING';
  private static $definition_mask = '';
  private static $definition_mask_str = '';
  private $parent_type = null;
  private $parent_name = '';

  function __construct($rules_xml, $parent){
    $this->parent_type = get_class($parent);
    $this->parent_name = $parent->name;

    if(!in_array($this->parent_type, array('table', 'view')))
        throw rbx::error("-- Rules can only be applied to tables or views");
    $this->rules_xml = $rules_xml;

    $pad = str_repeat("=", 10); $crlf = "[\r\n]+";
    self::$definition_mask = "#$pad <definition\s+signature='([a-z0-9]+)'> $pad$crlf(.*?)$crlf$pad </definition> $pad$crlf?#s";
    self::$definition_mask_str = "$pad <definition signature='%2\$s'> $pad\r\n%1\$s\r\n$pad </definition> $pad\r\n";
  }

  function alter_rules(){
    if($this->xml_def == $this->sql_def) return array();


    $todo = array();
    foreach($this->xml_def  as $rule_name=>$rule_infos){
        if($this->sql_def[$rule_name] == $rule_infos) continue;

        $event = strtoupper($rule_infos['event']);
        $where = $rule_infos['where']?"WHERE {$rule_infos['where']}":'';
        $definition = (string) $rule_infos['definition'];
        $signature  = $rule_infos['signature'];
        $todo[]= "CREATE OR REPLACE RULE \"$rule_name\" AS
            ON $event TO \"$this->parent_name\" $where
            DO INSTEAD $definition;";
        $todo[] = "COMMENT ON RULE \"$rule_name\"
            ON \"$this->parent_name\" IS ".CRLF
            ."E'".addslashes(sprintf(self::$definition_mask_str, $definition, $signature))."';";
    }
    foreach(array_keys(array_diff_key($this->sql_def, $this->xml_def)) as $rule_name)
        $todo[] = "DROP RULE IF EXISTS `$rule_name` ON `$this->parent_name`;";


    return $todo;

  }


  function get_sql_infos(){

    $query = "SELECT
        n.nspname AS schemaname,
        c.relname AS viewname,
        r.rulename as rulename,
        pg_get_ruledef(r.oid, true) AS compiled_definition,
        CASE ev_type WHEN 2 THEN 'update' WHEN 3 THEN 'insert' WHEN 4 THEN 'delete' END AS event,
        d.description AS description
      FROM 
        pg_rewrite AS r
        LEFT JOIN pg_class AS c ON c.oid = r.ev_class
        LEFT JOIN pg_namespace AS n ON n.oid = c.relnamespace
        LEFT JOIN pg_description AS d ON r.oid = d.objoid
      WHERE (r.rulename <> '_RETURN' AND c.relname='$this->parent_name')
        AND (CASE relkind WHEN 'v' THEN 'view' WHEN 'r' THEN 'table' END) = '$this->parent_type'
      ORDER BY r.rulename
    "; sql::query($query);
    $tmp = sql::brute_fetch('rulename');
    $rules = array();


    foreach($tmp as $rule_name=>$rule){
        preg_match(self::$definition_mask, $rule['description'], $out);
        $data = array(
            'compiled_definition'=>$rule['compiled_definition'],
            'definition'=>rtrim(myks_gen::sql_clean_def($out[2]),";"),
            'event'=>$rule['event'],
            'signature'=>$out[1],
            'where'=>'',
        );
        $rules[$rule_name] = $data;
    }

    return $rules;
  }

  function modified(){
    return $this->signatures['xml'] != $this->signatures['sql'];
  }

  function sql_infos(){
    if(!$this->sql_def)
        $this->sql_def = $this->get_sql_infos();

    $this->signatures['sql'] = array_extract($this->sql_def, 'signature');
  }

  private function calc_signature(&$rule_infos){
    $rule_infos['signature'] = substr(md5($rule_infos['compiled_definition']
        .$rule_infos['definition']),0,10);
  }


  function xml_infos() {

    $this->sql_infos(); //we need to self reflect

    $this->xml_def = array();

    foreach($this->rules_xml->rule as $rule) {
        $event = (string)$rule['on'];
        $where = (string)$rule['where'];
        $definition  = rtrim(myks_gen::sql_clean_def($rule),";");
        if(!$definition) $definition = self::rule_nothing;
        if($definition!=self::rule_nothing){
            $definition = "($definition)";
            $hash = substr(md5($event.$definition),0,5);
        } else $hash = "nothing";
        $rule_name = "{$this->parent_name}_{$event}_{$hash}";

        $compiled_definition = $this->sql_def[$rule_name]['compiled_definition'];

        $this->xml_def[$rule_name] = compact(
            'compiled_definition',
            'definition',
            'event',
            'where'
        ); $this->calc_signature($this->xml_def[$rule_name]);

    }

    $this->signatures['xml'] = array_extract($this->xml_def, 'signature');

  }


}
