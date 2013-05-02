<?php

abstract class job_client {

  protected $job_id    = 0;
  protected $worker_id = 0;
  protected $job_dir   = 'path://tmp';

  protected $progress = 0;
  protected $log_file = '';

  //Status
  const state_generating = 'generating';
  const state_done = 'done';
  const state_erroneous = 'erroneous';

  protected $status_file;
  protected $state = self::state_generating;

  public static $service = '';

  public function __construct($conf){
    $this->worker_id = (int)$conf['worker_id'];
    $this->job_id    = (int)$conf['job_id'];

    $this->job_dir = exyks_paths::resolve(yks::$get->config->job['dir'] . "/job_{$this->job_id}");
    files::create_dir($this->job_dir);
    $this->log_file = sprintf(yks::$get->config->logs['job'], $this->job_id);
    $this->log("Config is ".stripslashes(json_encode($conf)));

    //Init file for status
    $this->status_file = self::status_path($this->job_id);
    $this->set_state($this->state, 0, 'Init');
  }


  public static function status_path($job_id){
    return yks::$get->config->job['dir'] . "/job_{$job_id}.status";
  }

  //Abstract functions
  abstract protected function run();

  //Dump status for watcher
  protected function set_state($state, $progress = false, $message = ''){
    if(!in_array($state, array(self::state_generating, self::state_done, self::state_erroneous)))
      throw new Exception("Invalid job state: $state");
    $data = compact('state', 'progress', 'message');
    $data['job'] = $this->job_id;
    $data['time'] = microtime(true);
    file_put_contents($this->status_file, json_encode($data));
    $this->progress = $progress;
  }

  protected function done($details = ''){
    $this->log("Job finished.");
    $this->set_state(job_client::state_done);
    $ws = $this->ws(yks::$get->config->worker->server['service']);
    $ws->NotifyJobDone($this->worker_id, $this->job_id, json_encode($details));

    //Cleanup working dir
    if(is_dir($this->job_dir))
      files::delete_dir($this->job_dir);
  }


  protected function ws($service) {
    $url = sprintf("%s/services/?class=%s&wsdl", yks::$get->config->worker->server['url'], $service);
    return new SoapClient($url);
  }


  protected function log($message, $level='info'){
    $message = "[job #{$this->job_id}]$message";

    if(OS_WINDOWS)
      exec("title $message");

    worker_client::log_generic($this->log_file,  $message, $level);
  }


  /******* External API **************/

  //Helper to start a job from a forker process
  public static function create($worker_id, $job_id, $service){

    $ws = self::ws($service);
    worker_client::load_context($worker_id);
    rbx::ok("Context loaded");

    $job = null;
    try{
      //Get next job
      $job_conf = json_decode($ws->GetJobDetails($worker_id, $job_id), true);

      $job_conf['worker_id'] = $worker_id;
      $class = $job_conf['job_class'];
      unset($job_conf['job_class']);

      if(!isset($class))
        throw new Exception("Invalid job type: {$job_conf['job_type']}");

      //Start job !
      $job = new $class($job_conf);
      $job->run();
    }catch(Exception $e){
      //Report job erroneous state
      if($job) {
        $job->set_state(job_server::state_erroneous, false, $e->getMessage());
        $job->log($e->getMessage(), 'error');
      } else {
        rbx::error($e->getMessage());
      }
    }

    exit(0); // end of thread
  }

}