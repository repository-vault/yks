<?php

abstract class job_server extends _sql_base {
  const sql_table = 'ks_jobs';
  const sql_history_table = 'ks_jobs_history';
  const sql_constraint_table = 'ks_jobs_type_constraint';
  const sql_key = 'job_id';

  protected $sql_table = self::sql_table;
  protected $sql_key = self::sql_key;
  protected $manager = "job_server_manager";

  //Priority by default
  const PRIORITY_BACKGROUND = 0;
  const PRIORITY_DEFAULT = 1;
  const PRIORITY_CLIENT = 5;
  const PRIORITY_ADMIN = 10;

  //States
  const state_created     = 'created';
  const state_published   = 'published';
  const state_generating  = 'generating';
  const state_done        = 'done';
  const state_erroneous   = 'erroneous';

  private static $states  = array(self::state_created, self::state_published, self::state_generating, self::state_done, self::state_erroneous);
  public static $db_types = array();

  protected static $job_type = '';

  /**
  * Must be overload
  * Return information for client side job
  *
  * @return array
  */
  public function get_description(){
    return array(
        'job_id'    => $this->job_id,
        'job_type'  => $this->job_type,
        'job_class' => 'job_client_'.job_type::$db_types[$this->job_type]['type_class'],
    );
  }

  //Abstracts
  abstract public function post_done($data = null);

  public static function from_ids($ids){
    $ret = self::from_where(array(self::sql_key => $ids));
    $ret = array_sort($ret, $ids);
    return $ret;
  }

  public static function from_where($where){
    //Load all the jobs types
    $from = array(
      self::sql_table,
      job_type::sql_key => job_type::sql_table
    );

    sql::select($from, $where, self::sql_key.', '.job_type::sql_key);
    $res = sql::brute_fetch();
    $types = array_reindex($res, array(job_type::sql_key, self::sql_key));

    if(!$types)
      return array();

    $jobs = array();
    foreach($types as $type => $keys){
      if(!isset(job_type::$db_types[$type]))
        throw new Exception("Invalid job type $type");

      $verif_jobs = array(self::sql_key => array_keys($keys));
      //Given the types, load extra data form db and instanciate correct class
      $conf = job_type::$db_types[$type];
      $from = array(
        self::sql_table,
        self::sql_key => $conf['type_table'],
      );

      $tmp  = parent::from_where('job_server_'.$conf['type_class'], $from, self::sql_key, $verif_jobs); //very smart
      $jobs = array_merge_numeric($jobs, $tmp);
    }

    return $jobs;
  }

  public static function instanciate($id){
    $where =  array(
      self::sql_key => $id
    );
    return reset(self::from_where($where));
  }

  /**
  * @todo refacrtor movie trascode?????
  *
  */
  public static function create(){
     $job = job_server_manager::create(job::type_movie_transcode, $movie, $extra_data);
    if(!$job)
      throw new Exception("No job created");

    return $job;
  }

  public function add_history($job_state, $worker_id = null){
    //Check the state
    if(!in_array($job_state, self::$states ))
      throw new Exception("Invalid job state");

    //Update the job state
    $where = array(
      'job_id'    => $this->job_id,
      'job_state' => $this->job_state,
    );
    if(!sql::update(self::sql_table, compact('job_state'), $where))
      throw new Exception("Concurrency  check : job already updated for this state ($job_state vs. {$this->job_state})");

    //Build & add history
    $data = array(
      'job_id'     => $this->job_id,
      'job_state'  => $job_state,
      'job_time'   => microtime(true),
      'worker_id'  => $worker_id,
    );
    sql::insert(self::sql_history_table, $data);

    //Update worker job, for current job working
    // on working, set job_id
    // on done or error, set to null
    if($worker_id){
      if($job_state == self::state_generating)
        $job_id = $this->job_id;
      else if(in_array($job_state, array(self::state_done, self::state_erroneous)))
        $job_id = null;
        sql::update(worker_server::sql_table, compact('job_id'), compact('worker_id'));
    }

  }

  public function get_history($index = false){
    sql::select(self::sql_history_table, array('job_id' => $this->job_id), '*', 'order by job_time desc');
    return sql::brute_fetch($index);
  }

  public function update_progress($job_progress){
    sql::update(self::sql_table, compact('job_progress'), $this);
  }

  public function update_priority($job_priority){
    sql::update(self::sql_table, compact('job_priority'), $this);
  }

  //Request publication
  public function publish($priority = self::PRIORITY_DEFAULT){
    //Check state
    if(false && !$this->is_editable())
      throw new Exception("The job must be in initial state to be published.");

    if($priority != $this->job_priority)
      $this->update_priority($priority);

    $this->add_history(self::state_published);
  }

  //Attach to a worker
  public function attach(worker_server $worker){
    if($this->job_state != self::state_published)
      throw new Exception("The job must be in published state to be generating.");

    $this->add_history(self::state_generating, $worker->worker_id);
  }

  public function get_secure_hash(){
    return md5("{$this->job_id}:{$this->movie_id}");
  }

  public function is_editable(){
    return in_array($this->job_state, array(self::state_created, self::state_done));
  }

  protected function add_constraint($constraint_type, $data, $replace = false){
    if(!$replace && job_type_constraint::search($this->job_type,  $constraint_type)){
      return;
    }

    job_type_constraint::replace($this->job_type,  $constraint_type, $data);
  }
}
