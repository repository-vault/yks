<?php


class query  extends _sql_base {

  public $sql_query;
  public $data_results;
  public $data_headers;


  const sql_table = 'ks_queries_list';
  protected $sql_table = 'ks_queries_list';
  protected $sql_key = "query_id";
  const sql_key = "query_id";
  protected $manager = "queries_manager";

  public $ready = false;


  protected function __construct($from, $headers ) {
    parent::__construct($from);
    $this->data_headers = $headers;
  }

  function instanciate($from, $headers = array()) {
    return new self($from, $headers);
  }

  public function prepare($params_values){
    $sql_query = specialchars_decode($this->query_def);


   $ready = true;
   //error_log(print_r($this->params_list,1));

    foreach($this->params_list as $param_uid=>$param) {
        $ready &= isset($params_values[$param_uid]);
        $parameter = is_array($params_values[$param_uid]) ? join(',',$params_values[$param_uid]) : $params_values[$param_uid];
        $str = '';
        if($param->query_usage['param_field'])
            $str = sql::conds($param->query_usage['param_field'], $parameter);
        else $str = sql::clean($parameter);

        $sql_query = str_replace($param->query_usage['search_mask'], $str, $sql_query);
    }

    $this->ready = $ready;
    $this->sql_query = $sql_query;
    return true;

  }


  public function execute(){

    if(!$this->ready)
        throw rbx::error("Query is not ready");


    $res = sql::query($this->sql_query);
    if($res === false)
        throw new Exception("Query failed");


    $this->data_results = sql::brute_fetch();


    if(!$this->data_headers && $this->data_results)
      $this->data_headers =  array_combine($tmp = array_keys(first($this->data_results)), $tmp);
  }



  function trash(){
    $this->sql_delete();
  }

  function update($data){
    queries_manager::query_verify($data);
    $this->sql_update($data);
  }



  public function __toString(){
    return "query #{$this->query_id}";
  }


   public static function fast_export($sql_query, $creator = "Anonymous"){

    $res = sql::query($sql_query);

    $data_headers = array();
    for ($i = 0, $max=pg_num_fields($res); $i < $max; $i++) {
      $data_headers[$fieldname = pg_field_name($res, $i)] = array(
          'name'=> $fieldname ,
          'column_type'=> exyks_renderer_excel::pg_to_excel_type(pg_field_type($res, $i)),
      );
    }

    $data_results = sql::brute_fetch();

    return exyks_renderer_excel::export($data_headers, $data_results, array(
      'title'    => exyks::$head->title,
      'creator'  => $creator,
    ));

  }

}
