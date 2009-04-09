<?


abstract class rules_base {
  private $rules_xml = null;
  public $sql_def = array();
  public $xml_def = array();

  const rule_nothing = 'NOTHING;';
  private static $definition_mask = '';
  private static $definition_mask_str = '';
  private $parent_type = null;
  private $table_name = '';
  function __construct($rules_xml, $parent){
    $this->parent_type = get_class($parent);
    $this->table_name = $parent->name;
    if(!in_array($this->parent_type, array('table', 'view')))
        throw rbx::error("-- Rules can only be applied to tables or views");
    $this->rules_xml = $rules_xml;

    $pad = str_repeat("=", 10); $crlf = "[\r\n]+";
    self::$definition_mask = "#$pad <definition\s+signature='([a-f0-9]+)'> $pad$crlf(.*?)$crlf$pad </definition> $pad$crlf?#s";
    self::$definition_mask_str = "$pad <definition signature='%2\$s'> $pad\r\n%1\$s\r\n$pad </definition> $pad\r\n";
  }

  function alter_rules(){
    if($this->xml_def == $this->sql_def) return array();

    $table_name = $this->table_name;
    $inserts = $drops = array();
    $inserted_ghosts = $droped_ghosts = array();
    foreach($this->xml_def as $rule_name=>$rule_infos){
        $event = strtoupper($rule_infos['event']);
        $where = $rule_infos['where']?"WHERE {$rule_infos['where']}":'';
        $definition = (string) $rule_infos['definition'];
        if(!$definition) $definition = self::rule_nothing;
        $format = $definition==self::rule_nothing ? "%s":"(%s)";
        $definition = sprintf($format, $definition);
        $inserted_ghosts = "CREATE RULE `$rule_name` AS
            ON $event TO `$table_name` $where DO INSTEAD $definition;";
        $droped_ghosts[] = "DROP RULE `$rule_name` ON `$table_name`;";
        $inserts[]= "CREATE RULE \"$rule_name\" AS
            ON $event TO \"$table_name\" $where
            DO INSTEAD $definition;";
    } foreach(array_keys(array_diff_key($this->sql_def, $this->xml_def)) as $rule_name)
        $drops[] = "DROP RULE `$rule_name` ON `$table_name`;";
    return array_merge($drops, $inserts);

        //now, we create ghosts and we calculate their signature
    array_walk($inserts, array('sql', 'query'));
    $ghosts_contents = $this->sql_infos();
    array_walk($drops, array('sql', 'query'));
    foreach(array_keys($inserts) as $rule_name)
        $signatures[] = "COMMENT ON RULE \"$rule_name\" ON \"$table_name\" IS "
            ."E'".addslashes(sprintf(self::$definition_mask_str, $this->xml_def['def'], $signature))."';";

    return array_merge($drops, $inserts, $signatures);
  }


  function get_sql_infos($table_name){

    $row = array();
    $query = "SELECT
        n.nspname AS schemaname,
        c.relname AS viewname,
        r.rulename as rulename,
        pg_get_ruledef(r.oid) AS definition,
        CASE ev_type WHEN 2 THEN 'update' WHEN 3 THEN 'insert' WHEN 4 THEN 'delete' END AS event,
        d.description AS description
      FROM 
        pg_rewrite AS r
        LEFT JOIN pg_class AS c ON c.oid = r.ev_class
        LEFT JOIN pg_namespace AS n ON n.oid = c.relnamespace
        LEFT JOIN pg_description AS d ON r.oid = d.objoid
      WHERE (r.rulename <> '_RETURN' AND c.relname='$this->table_name')
        AND (CASE relkind WHEN 'v' THEN 'view' WHEN 'r' THEN 'table' END) = '$this->parent_type'
      ORDER BY r.rulename
    "; sql::query($query);
    $rules = array();

    foreach(sql::brute_fetch('rulename') as $rule_name=>$rule_infos){
        preg_match(self::$definition_mask, $row['description'], $out);
        $data = array(
            'base_definition'=>myks_gen::sql_clean_def($out[2]),
            'base_signature'=>(string)$out[1],
        );
        $data['signature'] = self::calc_signature($data);
        $rules[$rule_name] = $data;
    }
    return $rules;
  }

  function sql_infos(){
    $this->sql_def = $this->get_sql_infos($table_name);
    return count($this->sql_def);
  }

  static function calc_signature($view_name){
    return "pl";
    $definition = $view_infos['definition'];
    $definition = str_replace($view_name, $base_name, $definition);
    return crpt($definition.$view_infos['base_definition'],'FLAG_SQL',8);
  }


  function xml_infos() {
    $this->xml_def = array();
    foreach($this->rules_xml->rule as $rule) {
        $event = (string)$rule['on'];
        $where = (string)$rule['where'];
        $definition  = myks_gen::sql_clean_def($rule);
        $hash = substr(md5($event.$definition),0,5);
        $name = "{$this->table_name}_{$event}_{$hash}";
        $this->xml_def[$name] = compact('name', 'definition', 'event', 'where'); //instead...
    } return count($this->xml_def);
  }


}