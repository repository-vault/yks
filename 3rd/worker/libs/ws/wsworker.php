<?php
class WSWorker {
  /**
  * Load worker configuration for a given host name
  *
  * @param int $worker_id
  * @return string
  */
  public static function InitWorker($worker_id){
    $worker = self::LoadWorker($worker_id);
    $worker->clear_commands();
    return $worker->export(true);
  }

  /**
  * Get the command of a worker (Run|StartJob|StopJob|Shutdown)
  * depending on its state
  *
  * @param int $worker_id
  * @param string $current_state
  * @param int $job_id
  * @return string
  */
  public static function GetWorkerCommand($worker_id, $current_state, $job_id){

    //Load worker & tick
    $worker = self::LoadWorker($worker_id);
    $worker->tick();

    //Poll requests to db
    $timeout = timeout::from_seconds(2);

    do {
      //Get next worker command
      $command = $worker->pop_command();
      if($command)
        return json_encode(array(
          'command' => $command['worker_command'],
          'job_id'  => $command['job_id'],
        ));

      // Start a new job - this should be done elsewhere
      if($current_state == worker_server::state_wait){
        $job = $worker->get_next_job();

        if($job)
          $worker->push_command(worker_server::cmd_start_job, $job->job_id);
      }

      $timeout->sleep(1);
    } while(!$timeout->expired);

    //Default command, keep on.
    return json_encode(array(
      'command' => worker_server::cmd_ping,
    ));
  }

  /**
  * Get a job that needs a worker
  *
  * @param int $worker_id
  * @param int $job_id
  * @return string
  */
  public static function GetJobDetails($worker_id, $job_id){
    $worker = self::LoadWorker($worker_id);

    //Get & check job !
    $job = job_server::instanciate($job_id);
    //Update status
    $job->attach($worker);

    return json_encode($job->description);
  }


  /**
  * Notify a progress in a job
  *
  * @param int $worker_id
  * @param int $job_id
  * @param string $state
  * @param int $progress
  * @return string
  */
  public static function NotifyJobState($worker_id, $job_id, $state, $progress){
    syslog(LOG_ERR, "Worker:$worker_id job:$job_id state:$state progress:$progress");
    $worker = self::LoadWorker($worker_id);

    //Load job & update progress
    try{
      $job = job_server::instanciate($job_id);
      if(!$job)
        throw new Exception("No job found for $job_id");
      if($job->job_id != $worker->job_id || $job->job_state != job_server::state_generating)
        throw new Exception("Invalid job for this worker");
      $job->update_progress($progress);
      if($state != $job->job_state)
      $job->add_history($state, $worker_id);
    }catch(Exception $e){
      throw new SoapFault("WorkerException", "No job: ".$e->getMessage());
    }
  }


  /**
  * End of work for a job
  *
  * @param int $worker_id
  * @param int $job_id
  * @param string $job_details
  * @return string
  */
  public static function NotifyJobDone($worker_id, $job_id, $job_details){
    syslog(LOG_ERR, "Worker:$worker_id job:$job_id Job DONE");
    $worker = self::LoadWorker($worker_id);
    $job_details = json_decode($job_details);

    //Load job & update progress
    try{
      $job = job_server::instanciate($job_id);
      if(!$job)
      throw new Exception("No job found for $job_id");

      $job->update_progress(100);
      $job->add_history(job_server::state_done, $worker_id);
      $job->post_done($job_details);

    }catch(Exception $e){
      throw new SoapFault("WorkerException", "No job: ".$e->getMessage());
    }

  }




  /**
  * Helper to load and check worker
  *
  * @param int $worker_id
  *
  * @return worker_server
  */
  protected static function LoadWorker($worker_id){
    try{
      $worker = worker_server::instanciate($worker_id);
      if(!$worker)
      throw new Exception("No worker found");
      $worker->check($_SERVER['REMOTE_ADDR']);
    }catch(Exception $e){
      throw new SoapFault("WorkerException", "No worker: ".$e->getMessage());
    }
    return $worker;
  }
}
