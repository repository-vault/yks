<?

class query  extends _sql_base {

  
  const sql_table = 'ks_queries_list';
  protected $sql_table = 'ks_queries_list';
  protected $sql_key = "query_id";
  const sql_key = "query_id";
  protected $manager = "queries_manager";

  public $ready = false;

  public function prepare_query($params_values){
    $sql_query = $this->query_def;

    $ready = true;

    foreach($this->params_list as $param) {
        $param_key = $param['param_key'];

        $ready &= isset($params_values[$param_key]);
        $str = '';
        if($param->query_usage['param_field'])
            $str = sql::conds($param->query_usage['param_field'], $params_values[$param_key]);
        else $str = sql::clean($params_values[$param_key]);

        $sql_query = str_replace($param->query_usage['search_mask'], $str, $sql_query);
    }

    $this->ready = $ready;

    return $sql_query;
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