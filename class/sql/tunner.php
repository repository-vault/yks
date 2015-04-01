<?php

class sql_tunner {

  function __construct($query){
    $this->query = $query;

    //$combi = array('bitmapscan', 'mergejoin');
    $combi = array('indexscan', 'seqscan', 'sort', 'hashjoin', 'hashagg');
    $this->set_options($combi);
  }

  function set_options($options){
    $this->options    = $options;
    $this->options_nb = 1 << count($this->options);
  }

  function forge_plan($set){
    $plan = array();
    foreach($this->options as $k=>$opt) {
      $status = $set & (1<<$k) ? "true" : "false";
      $plan[] = "SET enable_$opt = $status;";
    } return $plan;
  }
  function apply_plan($plan){
    foreach($plan as $query)
      sql::query($query);
  }

  function explain($plan){
    $query = $this->query;
    $this->query = "EXPLAIN {$this->query}";
    $data = $this->run($plan, true);
    echo join(CRLF, $data).CRLF;
    $this->query = $query;
  }

  function run($plan, $trace = false){
    if(is_numeric($plan))
      $plan = $this->forge_plan($plan);

    $this->apply_plan($plan);
    echo join(', ', $plan).CRLF;
    $start_time = microtime(true);
    sql::query($this->query);
    if($trace) {
      $data = sql::brute_fetch(false, "QUERY PLAN");
      return $data;
    }

    $duration = microtime(true) - $start_time;
    return $duration;
  }

  function set($k, $v){
    rbx::ok("Set $k to $v");
    sql::query("SET $k=$v;");
  }
  function show($k){
    return sql::qvalue("SHOW $k");
  }

  function bench(){
    $results = array();

    for($set=0; $set < $this->options_nb; $set++) {
      $results[$set] = $duration = $this->run($set);
      rbx::ok("combi $set /{$bench->options_nb} Took $duration");
    }
    print_r($results);
  }
}