<?php



abstract class procedure_base extends myks_installer  {

/**
* type
* setof
* ->def
**/

  public $sql_def = array();
  public $xml_def = array();
  private $proc_name;

  function __construct($name, $proc_xml){
    $this->proc_name  = $name;
    $this->proc_xml   = $proc_xml;
  }

  function modified(){
 
    $same = $this->xml_def == $this->sql_def;
    if($same) return !$same;

    //print_r($this->xml_def);print_r($this->sql_def);die;
    //print_r(array_show_diff($this->xml_def, $this->sql_def,"xml","sql"));die;

    return !$same;
  }

  function get_name(){
    return $this->proc_name;
  }

  function delete_def(){
    $queries = array();
    $queries[] = "DROP FUNCTION {$this->proc_name['safe']}()"; //signature
    return $queries;
  }

  function alter_def(){
    $todo = array();
    if(!$this->modified())
        return $todo;

        //on transtype ici (à la facon de ce qui est fait dans mykse->XXX_mode()
    $transtype = array(
        'string'  => "varchar",
        'mini'    => "smallint",
        'small'   => "smallint",
        'int'     => "integer",
        'big'     => "integer",
        'giga'    => "bigint",
        'float'   => "double precision",
        'decimal' => "float(10,5)",
        'bool'    => "boolean",
        'sql_timestamp' => "timestamptz",
        'text'    => "text",
    );

    //usefull for debug
    //print_r(array_show_diff($this->xml_def, $this->sql_def, 'xml', 'sql'));//sql::$queries);die;

    $args=array();
    foreach((array)$this->xml_def['params'] as $param) {
        $type = pick($transtype[$param['type']], $param['type']);
        $args[] = $param['name'].' '.$type;
    }

    $args = join(', ',$args);

    $volatility = $this->xml_def['volatility'];

    $out  = $this->xml_def['setof'].' '.pick($transtype[$this->xml_def['type']], $this->xml_def['type']);
    $ret = "CREATE OR REPLACE FUNCTION {$this->proc_name['safe']}($args) RETURNS $out AS\n\$body\$\n";
    $ret.= $this->xml_def['def']."\n\$body\$\n";
    $ret.= "LANGUAGE 'plpgsql' $volatility CALLED ON NULL INPUT SECURITY INVOKER;\n\n";

    return array($ret);
  }
//return all procedures matching search criteria
  static function sql_search($proc_name, $proc_schema, $type, $params = array()){
    $find = self::raw_sql_search($proc_name, $proc_schema, $type, $params);
    $ret = array(); 
    foreach($find as $infos){
        $tmp = sql::resolve("{$infos['routine_schema']}.{$infos['routine_name']}");
        $tmp = new procedure($tmp, new stdclass());;
        $ret[$tmp->hash_key()] = $tmp;
    }
    return $ret;
  }

//STATIC
  private static function raw_sql_search($proc_name, $proc_schema, $data_type, $params = array()){

    $having    = array();
    $having  []= "COUNT(parameters.specific_name) = ".count($params);

    //concat_comma(information_schema.parameters.ordinal_position)

    if($params) {
      $params_types = array();
      foreach($params as $param)
        $params_types[] = $param['type'];

      $having  []= " concat_comma(parameters.data_type) = '".join(', ',$params_types)."'";
    }

    $query = "SELECT
            routine_name, routine_schema, specific_name
        FROM
          zks_information_schema_routines
          LEFT JOIN (
              SELECT *
                FROM 
                zks_information_schema_parameters
                ORDER BY ordinal_position ASC
          ) AS parameters USING(specific_name)

        WHERE
                 routine_name LIKE '{$proc_name}'
            AND  routine_schema='{$proc_schema}'
            AND  zks_information_schema_routines.data_type='$data_type'
        GROUP BY specific_name, routine_schema, routine_name
        HAVING ".join(' AND ', $having);


    sql::query($query);
    return sql::brute_fetch("specific_name");
  }

  function sql_infos(){

    $find = self::raw_sql_search(
        $this->proc_name['name'],
        $this->proc_name['schema'],
        $this->xml_def['type'],
        $this->xml_def['params'] );


    $specific_name = key($find);

    if(!$specific_name){
        rbx::ok("-- New procedure : {$this->proc_name['name']}");
        return false;
    }

    $verif_proc = array(
        'routine_type'   => 'FUNCTION',
        'routine_name'   => $this->proc_name['name'],
        'specific_name'  => $specific_name,
        'routine_schema' => $this->proc_name['schema']
    ); $data = sql::row("zks_information_schema_routines", $verif_proc);

    if(!$data) return false;

    $this->sql_def = array(
        'name'       => $data['routine_name'],
        'setof'      => $data['routine_setof'],
        'volatility' => $data['volatility'],
        'type'       => $data['data_type'],
        'def'        => myks_gen::sql_clean_def($data['routine_definition']),
    );

    $specific_name    = $data['specific_name'];
    $verif_proc_inner = compact('specific_name');

    sql::select("zks_information_schema_parameters", $verif_proc_inner,
        '*', 'ORDER BY ordinal_position');
    while($l = sql::fetch()){
        $this->sql_def['params'][]=array(
            'type' => $l['data_type'],
            'name' => $l['parameter_name'],
        );
    }
  }

  function xml_infos(){
    $def  = pick($this->proc_xml->def, $this->proc_xml['def']);
    $data = array(
        'name'        => (string)  $this->proc_name['name'],
        'type'        => (string)  $this->proc_xml['type'],
        'setof'       => (string) $this->proc_xml['setof'],
        'volatility'  => (string) $this->proc_xml['volatility'],
        'def'         => sql::unfix(myks_gen::sql_clean_def($def)),
    );

    if($this->proc_xml->param)
      foreach($this->proc_xml->param as $param_xml){
        $data['params'][]=array(
            'type'    => (string)$param_xml['type'],
            'name'    => isset($param_xml['name'])?(string)$param_xml['name']:null,
        );
    }

    $this->xml_def=$data;
  }
}

