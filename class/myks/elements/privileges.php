<?

class privileges {
  private static $root_privileges = array();
  private  $grants_xml;
  private  $element_name;
  private  $element_type;


  private $sql_def;
  private $xml_def;

  function __construct($grants_xml, $element_infos, $element_type){
    $this->element_type  = $element_type;

    $this->element_infos = $element_infos;
    $this->element_name  = $element_infos['name'];
    $this->element_name_safe  = $element_infos['safe'];

    $this->grants_xml = $grants_xml;
  }

  static function declare_root_privileges($config){
    if(!$config) return; $privileges = array();
    foreach($config->grant_all as $grant){
        $type = (string)$grant['on'];
        $privileges[$type] = self::merge($privileges[$type] , self::parse($grant));
    }self::$root_privileges = $privileges;
  }


  function alter_def(){
    $todo = array();
    if($this->sql_def== $this->xml_def) return array();

    $drops = array_diff_key($this->sql_def, $this->xml_def);
    foreach($this->xml_def as $to=>$def){
        $current = (array)$this->sql_def[$to];
        if($current == $def) continue;
        if($erase = array_diff($current, $def)) $drops[$to] = $erase;
        if(!($missing = array_diff($def, $current))) continue;
        $missing = join(',', $missing);
        $todo[] = "GRANT $missing ON $this->element_name_safe TO ".self::to($to);
    } foreach($drops as $to=>$def){
        $def = join(',', $def);
        $todo[] = "REVOKE $def ON $this->element_name_safe FROM  ".self::to($to);
    }
    return $todo;

  }

  function modified(){
    return $this->sql_def != $this->xml_def;
  }

  function to($to){
    return ($to != "PUBLIC")?'"'.$to.'"':$to;
  }

  function sql_infos(){
    $verif_table = array('table_name'=>$this->element_name);
    sql::select("information_schema.table_privileges", $verif_table);
    $this->sql_def = sql::brute_fetch_depth('grantee', 'privilege_type', false);
  }

  function xml_infos(){
    $privileges = self::$root_privileges[$this->element_type];
    foreach($this->grants_xml->grant as $grant)
        $privileges = self::merge($privileges, self::parse($grant));
    $this->xml_def = (array)$privileges;
  }

  private static function parse($grant){
    if(!$grant['actions']) $grant['actions']="select";
    if(!$grant['to']) $grant['to']="PUBLIC";
    $vals = preg_split(VAL_SPLITTER, strtoupper($grant['actions']));
    $to   = preg_split(VAL_SPLITTER, $grant['to']);
    return array_fill_keys($to, array_combine($vals, $vals));
  }

  private static function merge($grant1, $grant2){
    return array_merge_numeric((array)$grant1, (array)$grant2);
  }


}
