<?php

class worker_server extends _sql_base {
  const sql_table = 'ks_jobs_worker';
  const sql_table_command = 'ks_jobs_worker_command';
  const sql_key = 'worker_id';

  protected $sql_table = self::sql_table;
  protected $sql_key = self::sql_key;

  //Commands
  const cmd_ping  = 'Ping';
  const cmd_pause = 'Pause';
  const cmd_run   = 'Run';
  const cmd_start_job = 'StartJob';
  const cmd_abort_job = 'AbortJob';
  const cmd_shutdown = 'Shutdown';
  public static $cmds= array(self::cmd_ping, self::cmd_run, self::cmd_pause, self::cmd_shutdown, self::cmd_start_job, self::cmd_abort_job);

  //States
  const state_wait    = 'Wait';
  const state_paused  = 'Paused';
  const state_working = 'Working';

  public $rights = array();

  public static function from_where($where){

    $res = parent::from_where(__CLASS__, self::sql_table, self::sql_key, $where);

    sql::select('ks_jobs_worker_capacity', array(self::sql_key => array_keys($res)));
    $capacity_list = sql::brute_fetch();

    foreach($capacity_list as $capacity){
      $job_name = $capacity[job_type::sql_key];

      $res[$capacity[self::sql_key]]->rights[$job_name] = job_type::$db_types[$job_name]['data'];
    }

    return $res;
  }

  public static function instanciate($worker_id){
    return first(self::from_where(array(self::sql_key => $worker_id)));
  }

  //Check this is the correct ip address for the worker
  public function check($ip){
    if($this->worker_ip != $ip)
      throw new Exception("Invalid ip");
  }

  public function tick(){
    sql::update(self::sql_table, array('worker_last_tick' => time()), $this);
  }


  /**
  * Return all avalaible job_type without constraint
  *
  * @return job_type[]
  */
  private function get_types_constraint(){

    sql::select(job_server::sql_constraint_table, array(job_type::sql_key => array_keys($this->rights)));
    $constraint_list = sql::brute_fetch();

    $rights = $this->rights;

    foreach($constraint_list as $constraint){
      switch ($constraint['job_constraint']) {
         case 'wait':
            if($_SERVER['REQUEST_TIME'] > $constraint['data']){
              $where = array(
                job_type::sql_key => $constraint[job_type::sql_key],
                'job_constraint'  => $constraint['job_constraint'],
              );
              sql::delete(job_server::sql_constraint_table, $where);
            }
            else{
              if(isset($this->rights[$constraint[job_type::sql_key]])){
                unset($rights[$constraint[job_type::sql_key]]);
              }
            }
           break;
         default:
          Throw New Exception('Job constraint doesn t exist');
          break;
      }
    }

    return $rights;
  }

  //Search next job that this worker can do
  // according to its right
  public function get_next_job(){
    $avalaible_rights = $this->get_types_constraint();

    if(empty($avalaible_rights)){
      return false;
    }

    $where = array(
      //Both state set to published
      sql::fromf(job_server::sql_table.'.job_state') => job_server::state_published,
      sql::fromf(job_server::sql_history_table.'.job_state') => job_server::state_published,
      job_type::sql_key => array_keys($avalaible_rights),
    );
    $from = array(
      job_server::sql_table,
      job_server::sql_key => job_server::sql_history_table,
    );
    $job_id = sql::value($from, $where, job_server::sql_key, 'ORDER BY job_priority DESC, job_time ASC');
    if(!$job_id)
      return false;
    return job_server::instanciate($job_id);

  }


  /**
   * flush all command pipe
   */
  public function clear_commands(){
    sql::delete(self::sql_table_command, $this);
  }


  //Get last stacked command
  public function pop_command(){
    $where = array(
      worker_server::sql_key => $this->worker_id,
    );
    $command = sql::row(self::sql_table_command, $where, '*', 'ORDER BY command_date ASC');
    if(!$command)
      return false;
    sql::delete(self::sql_table_command, $command);
    return $command;
  }


  function free(){
    $this->sql_update(array(job_server::sql_key => null));
  }

  //Add a command to the stack
  public function push_command($worker_command, $job_id = null){
    if(!in_array($worker_command, self::$cmds))
      throw new Exception("Invalid worker command: $worker_command");
    $data = compact('worker_command', 'job_id');
    $data['command_date'] = time();
    $data['worker_id'] = $this->worker_id;
    sql::insert(self::sql_table_command, $data);
  }

  public function export($jsonify = false){
    //Actions Rights
    $rights = array();
    foreach($this->rights as $k => $v){
        $rights[] = $k;
    }

    //Get config files from workers
    $config_files = files::find(CONFIG_PATH.'/workers');
    $config_inline = array();
    foreach($config_files as $file)
      $config_inline = array_merge($config_inline, xml_to_dict($file, 'AS_'));

    $data = array(
      'worker_id' => $this->worker_id,
      'rights'    => $jsonify  ? json_encode($rights) : $rights,
      'config' => $jsonify ? json_encode($config_inline) : $config_inline,
    );

    return $jsonify ? json_encode($data) : $data;
  }
}