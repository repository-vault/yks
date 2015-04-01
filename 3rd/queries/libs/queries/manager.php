<?php

class queries_manager {

  function get_params_list(query $query) {

    $params_usage = self::get_params_usage($query->query_def);


    $verif_params = array(
        'param_key'=> array_extract($params_usage, "param_key", true),
    );


    $params_list = queries_param::from_where($verif_params);
    $params_list = array_reindex($params_list, 'param_key');

    $query->params_list = array();
    foreach($params_usage as $param_usage){

        $param = clone  $params_list[$param_usage['param_key']];
        $param->query_usage = $param_usage;

        $param->param_title = pick($param->query_usage['param_context'], $param->param_key);

        $query->params_list[ $param->query_usage['param_uid'] ] = $param;
    }

    return $query->params_list;
  }



  function get_params_usage($query_string){ //private

    $param_mask = "/#([^#\[\"]+)(?:\[(.*?)\])?(?:\"(.*?)\")?#/";
    if(!preg_match_all($param_mask, $query_string, $out, PREG_SET_ORDER))
        return array();

    $params_usage = array();
    foreach($out as $param) {
        list($search_mask,$param_key, $param_field, $param_context) = $param;
        $param_uid = md5($search_mask);
        $params_usage []= compact('param_key', 'param_field',
                                  'search_mask', 'param_context', 'param_uid');
    }
    return $params_usage;
  }


  static function create($data){

    try {
        self::query_verify($data);
        $query_id = sql::insert("ks_queries_list", $data, true);
        return query::instanciate($query_id);
    } catch(Exception $e){ throw rbx::error("Creation error"); }

  }

  static function query_verify($data){
        //verifie la signature des parametres de la query, existent-il vraiement ?
    if(!$data['query_name'])
        throw rbx::warn("You must specify a query name", "query_name");

    $params_usage  = self::get_params_usage($data['query_def']);
    $params_list   = array_reindex(queries_param::from_where(array("true")), "param_key");

    foreach($params_usage as $param_usage)
        if(! isset($params_list[$param_usage['param_key'] ]))
            throw rbx::error("Unknow parameter definition");


  }

}