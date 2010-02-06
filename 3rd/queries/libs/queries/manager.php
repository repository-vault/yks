<?

class queries_manager {

  function get_params_list(query $query) {

    $params_def = self::get_params_def($query->query_def);
    $verif_params = array(
        'param_key'=> array_keys($params_def),
    );


    $params_list = query_param::from_where($verif_params);
    $params_list = array_reindex($params_list, 'param_key');

    $query->params_list = $params_list;
    foreach($params_list as $param_key=>$param)
        $param->query_usage = $params_def[$param_key];

    return $query->params_list;
  }

  

  function get_params_def($query_def){ //private

    $param_mask = "/#([^#\[]+)(?:\[(.*?)\])?#/";
    if(!preg_match_all($param_mask, $query_def, $out, PREG_SET_ORDER))
        return array();
    $params_def = array();
    foreach($out as $param) {
        $search_mask = $param[0];
        $param_key = $param[1]; $param_field = $param[2];
        $params_def[$param_key] = compact('param_field', 'search_mask');
    }
    return $params_def;
  }


  static function create($data){

    try {
        self::query_verify($data);
        $query_id = sql::insert("ks_queries_list", $data, true);
        return new query($query_id);
    } catch(Exception $e){ throw rbx::error("Creation error"); }

  }

  static function query_verify($data){
        //verifie la signature des parametres de la query, existent-il vraiement ?
    if(!$data['query_name'])
        throw rbx::warn("You must specify a query name", "query_name");

    $params_def  = self::get_params_def($data['query_def']);
    $params_list = array_reindex(query_param::from_where(array("true")), "param_key");
    
    foreach($params_def as $param_key=>$param_usage)
        if(!isset($params_list[$param_key]))
            throw rbx::error("Unknow parameter definition");


  }

}