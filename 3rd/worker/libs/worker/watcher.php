<?php

class worker_watcher{

  private $worker_id = 0;
  private $sleep = 0.5;
  private static $pid = 0;
  private $ws = null;

  private $threaded = true;
  private $job_status = array();
  private $failed_reports = array();
  private $failed_reports_max = 5; // Allow 5 attempts for each report

  /**
  * @todo config class worker watcher
  *
  * @param mixed $worker_id
  * @param mixed $threaded
  * @param mixed $sleep
  * @return worker_watcher
  */
  public function __construct($worker_id, $threaded = true, $sleep = 0.5){
    $this->worker_id = $worker_id;
    $this->threaded = $threaded;
    $this->sleep = $sleep;
    $this->ws = new SoapClient(yks::$get->config->worker->server['url'].'/services/?class='.yks::$get->config->worker->server['service'].'&wsdl');
    $this->log("Watcher threaded ? ".($this->threaded ? 'Yes' : 'No'));
  }

  //Start forked process
  public function start(){
    if(!$this->threaded)
      return;

    if(self::$pid)
      return false;
    $this->log("Start watcher fork...");

    self::$pid = pcntl_fork();
    if(self::$pid == -1)
      throw new Exception("Failed to init fork.");

    if(self::$pid){
      //Parent
      $this->log("Forked with pid ".self::$pid);
      pcntl_wait($status, WNOHANG);
    }else{
      //Child
      $this->run();
    }
  }

  //Kill the fork
  public function stop(){
    if($this->threaded && self::$pid)
      posix_kill(self::$pid, SIGKILL);
  }

  public function run(){
    if($this->threaded){
      //Threaded mode
      while(true){
        $this->update_status();
        usleep($this->sleep * 1000000);
      }
    }else{
      //Direct mode
      $this->update_status();
    }
  }

  //Update the status found in dirs
  private function update_status(){
    $new_status = $this->find_status();
    foreach($new_status as $status){
      if(!$status['job'])
        continue;

      //Send notification to avs
      try{
        $this->ws->NotifyJobState($this->worker_id, $status['job'], $status['state'], $status['progress']);
        $this->log("Notified job #{$status['job']} on {$status['state']} ({$status['progress']})");
      }catch(Exception $e){
        $this->log("Notification failed for job #{$status['job']}: ".$e->getMessage(), 'error');
        if($status['state'] == job::state_generating)
          rename($status['status_path'], $status['status_path'].'.failed');

        //Limit failed reports attemps
        if(!$this->failed_reports[$status['status_path']])
          $this->failed_reports[$status['status_path']] = array();
        $this->failed_reports[$status['status_path']][time()] = $status;
        if(count($this->failed_reports[$status['status_path']]) > $this->failed_reports_max){
          //Dump attempts & remove status file so it won't come back
          $this->log("Stop sending this status: {$status['status_path']}");
          rename($status['status_path'], $status['status_path'].'.last_attempt');
          file_put_contents($status['status_path'].'.all_attempts', json_encode($this->failed_reports[$status['status_path']]));
        }else{
          continue; // try another time
        }
      }

      //Save updated status
      $this->job_status[$status['job']] = $status;

      //Cleanup
      if($status['state'] == job::state_done)
        files::delete_dir(yks::$get->config->job['dir']."/job_{$status['job']}");
    }
  }

  //List only the updated status
  private function find_status(){
    $dir = yks::$get->config->job['dir'];
    $status = files::find($dir, '#.status$#');

    $out = array();
    foreach($status as $s){
      $status = json_decode(file_get_contents($s), true);

      //Remove already used status
      $existing_status = $this->job_status[$status['job']];
      if($existing_status && $existing_status['time'] == $status['time'])
        continue;

      $status['status_path'] = $s;
      $out[] = $status;
    }

    return $out;
  }

  private function log($message, $level='info'){
    $file_path = sprintf(yks::$get->config->logs['worker'], $this->worker_id);
    worker_client::log_generic($file_path, 'Watcher', $message, $level);
  }

}