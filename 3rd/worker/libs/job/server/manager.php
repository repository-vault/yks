<?php
/**
* Job manager server side
*/
class job_server_manager {

  /**
  * Create new job
  *
  * @param string $type job type
  * @param array $extra_data additional data specific to job type
  *
  * @return job_server
  */
  public static function create($type, $extra_data = array()){

    //Check the type
    if(!isset(job_type::$db_types[$type]))
      throw new Exception("Invalid job type");

    $type = job_type::$db_types[$type];

    $token = sql::begin();

    //Create base job
    $data = array(
      job_type::sql_key => $type[job_type::sql_key],
    ); $job_id = sql::insert(job_server::sql_table, $data, true);

    if(!$job_id)
      throw new Exception("No job created");

    //Save extra data, before instanciating
    if($extra_data){
      $table = $type['type_table'];
      $extra_data[job_server::sql_key] = $job_id;
      sql::insert($table, $extra_data);
    }

    //Add initial history
    $job = job_server::instanciate($job_id);
    $job->add_history(job_server::state_created);

    sql::commit($token);

    return $job;
  }

  //Helper to display nicely a date using a diff or full date
  public function get_nice_date(job_server $job, $state, $limit_hours = 12){
    $where = array(
      'job_id'    => $job->job_id,
      'job_state' => $state,
    );
    $t = sql::value(job_server::sql_history_table, $where, 'job_time');
    if(!$t)
      return false;
    $diff = time() - $t;
    return $diff < $limit_hours * 3600 ? date::human_diff($diff) : date('d/m/Y H:i', $t);
  }

}