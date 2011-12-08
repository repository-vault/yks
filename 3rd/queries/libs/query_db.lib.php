<?php

class query_db  extends _sql_base {

  
  const sql_table = 'ks_queries_list';
  protected $sql_table = 'ks_queries_list';
  protected $sql_key = "query_id";
  const sql_key = "query_id";
  protected $manager = "queries_manager";

  public $ready = false;

  private $query;


  public function __construct($from){
    parent::__construct($from);
  }

  public function prepare($params_values){
    $sql_query = specialchars_decode($this->query_def);


   $ready = true;
    error_log(print_r($this->params_list,1));

    foreach($this->params_list as $param_uid=>$param) {
        $ready &= isset($params_values[$param_uid]);
        $str = '';
        if($param->query_usage['param_field'])
            $str = sql::conds($param->query_usage['param_field'], $params_values[$param_uid]);
        else $str = sql::clean($params_values[$param_uid]);

        $sql_query = str_replace($param->query_usage['search_mask'], $str, $sql_query);
    }

    $this->ready = $ready;
    $this->query = new query($sql_query);
    return true;

  }
  public function get_sql_query(){
    return (string) $this->query;
  }

  public function execute(){

    if(!$this->ready) 
        throw rbx::error("Query is not ready");
    $this->query->execute();

  }

  public function print_data(){

    $this->query->print_html_table_data();
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


}