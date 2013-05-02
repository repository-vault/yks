<?php

class worker_client {

  private $worker_id = 0;
  private $ws = null;

  private $rights = array();
  public static $conf = array();
  private $watcher = null; // Watcher instance
  private $running = true;

  //Current job
  private $job_id = 0;
  private $job_pid = 0;

  private $worker_pid = 0;
  private $log_file = "";
  private $pid_file = "";

  //Commands
  const cmd_ping  = 'Ping';
  const cmd_run   = 'Run';
  const cmd_pause = 'Pause';
  const cmd_start_job = 'StartJob';
  const cmd_abort_job = 'AbortJob';
  const cmd_shutdown = 'Shutdown';
  private static $commands = array(self::cmd_ping, self::cmd_run, self::cmd_pause, self::cmd_start_job, self::cmd_abort_job, self::cmd_shutdown);

  //States
  private $state = self::state_wait;
  const state_wait    = 'Wait';
  const state_paused  = 'Paused';
  const state_working = 'Working';
  private static $states = array(self::state_wait, self::state_paused, self::state_working);

  public function __construct($worker_id){

    $this->worker_id = $worker_id;
    $this->ws        = $this->ws(yks::$get->config->worker->server['service']);
    $this->log_file = sprintf(yks::$get->config->logs['worker'], $this->worker_id);
    $this->pid_file = sprintf(yks::$get->config->worker['pid'],  $this->worker_id);

    self::zeus_mode($this->worker_id); //kill your father

    //Load config from avs
    $this->log("Loading worker $worker_id");
    $conf = json_decode($this->ws->InitWorker($worker_id), true);
    $this->worker_id = (int)$conf['worker_id'];

    $this->rights = json_decode($conf['rights'], true);
    self::$conf   = json_decode($conf16['config'], true);
    $this->log("Worker #{$this->worker_id} : ".implode(', ', $this->rights));

    //Init watcher
    $this->watcher = new worker_watcher($this->worker_id, OS_LINUX); // threaded only on linux

    //Setup end of script, using graceful, SIGNIT & SIGTERM
    declare(ticks = 1);
    $cb = array($this, 'stop');
    register_shutdown_function($cb);

    if(OS_LINUX){
      pcntl_signal(SIGTERM, $cb);
      pcntl_signal(SIGINT, $cb);
      pcntl_signal(SIGQUIT, $cb);
    }
  }

  public static function zeus_mode($worker_id){
    $pid_file = sprintf(yks::$get->config->worker['pid'],  $worker_id);
    //Check already running
    if(file_exists($pid_file) && self::pslist($ghost_pid = file_get_contents($pid_file))) {
      //kill it softly
      self::pskill($ghost_pid); sleep(1);clearstatcache(true, $pid_file);
      if(self::pslist($ghost_pid))
        throw new Exception("Worker is already started on pid #{$ghost_pid}");
    }

  }

  private function store_context($vars){
    $ctx_file = sprintf(yks::$get->config->worker['ctx'], $this->worker_id);
    file_put_contents($ctx_file , serialize($vars));
  }

  public static function load_context($worker_id){
    $ctx_file = sprintf(yks::$get->config->worker['ctx'], $worker_id);
    $vars = unserialize(file_get_contents($ctx_file ));
    foreach($vars as $name=>$content){
      if(strpos($name, "::")) {
        list($class_name, $prop_name) = explode('::', $name);
        $class_name::$$prop_name = $content;
      }
    }
  }

  //Create a daemon for this worker
  public function dameonize(){
    if(OS_WINDOWS)
      throw new Exception("Can't daemonize on Windows. Use --debug");

    $pid = pcntl_fork();
    if($pid == -1)
      throw new Exception("Failed to init fork.");

    if($pid){
      //Parent
      pcntl_wait($status, WNOHANG);
    }else{
      //Child: worker!
      $this->run();
    }
  }

  public function run(){
    $this->worker_pid = getmypid();
    file_put_contents($this->pid_file, $this->worker_pid);
    rbx::ok("Started worker with pid #{$this->worker_pid} ");

    //Remove old jobs state, so it doesn't mess with the watcher
    self::store_context(array(__CLASS__."::conf" => self::$conf)); //for jobs
    files::create_dir(yks::$get->config->job['dir']);
    foreach(files::find(yks::$get->config->job['dir'], '#job-status$#') as $f)
      unlink($f);


    $this->watcher->start();

    while($this->running){

      //Read status from current job
      $job_generating = false;
      if($this->job_id){
        $status_file = job_client::status_path($this->job_id);
        if(file_exists($status_file)){
          $status = json_decode(file_get_contents($status_file), true);

          if($status['state'] == job_client::state_done) {
            $this->job_id = null;
            if($this->state == self::state_working)
              $this->state = self::state_wait;
            continue;
          }

          if($status['state'] == job_client::state_erroneous) {
            $this->job_id = null;
            if($this->state == self::state_working)
              $this->state = self::state_wait;
            continue;
          }

          $job_generating = ($status && ($status['state'] == job_client::state_generating));
          $this->log("Job #{$this->job_id} is generating ? ".($job_generating ? 'YES' : 'NO')); //then ouat ?

          if(!$job_generating && $this->state == self::state_working)
            $this->state = self::state_wait;
        }
      }

      // Ask for worker state
      try{
        $command = json_decode($this->ws->GetWorkerCommand($this->worker_id, $this->state, $this->job_id), true);


        if(!$command || !in_array($command['command'], self::$commands))
          throw new Exception("No state");
        $this->log("State {$this->state} > Command {$command['command']}");
      }catch(Exception $e){
        $this->log("Invalid worker command: ".$e->getMessage(), 'error');
      }

      switch($command['command']){
        case self::cmd_ping:
          break;
        case self::cmd_run:
          //Continue to run & wait
          $this->state = self::state_wait;
          break;
        case self::cmd_start_job:
          //Start a new job
          if($this->state == self::state_wait){
            $this->start_job((int)$command['job_id']);
            $this->state = self::state_working;
          }
          break;
        case self::cmd_abort_job:
          //Kill current job
          if($this->job_pid)
            self::pskill($this->job_pid);
          $this->state = self::state_wait;
          break;
        case self::cmd_pause:
          //Set in pause !
          if($this->state != self::state_paused){
            $this->log("Start paused mode.");
            $this->state = self::state_paused;
          }
          break;
        case self::cmd_shutdown:
          //Shutdown the worker
          $this->stop();
          break;
        default:
          throw new Exception("Unsuported command: {$command['command']}");
      }

      //On windows, run once watcher per cycle
      if(OS_WINDOWS)
        $this->watcher->run();

      sleep(3);
    }

    if($this->watcher)
      $this->watcher->stop();
  }

  //Start a job in a sperate thread
  private function start_job($job_id){
    $this->job_id = $job_id;
    $this->log("Start job #{$this->job_id} fork...");

    //Kill previous job
    if($this->job_pid)
      self::pskill($this->job_pid);

    if(OS_WINDOWS) {
      $cmd = cli::which("clyks")." ".join(' ', array_slice($_SERVER['argv'],1)). " --job_id={$job_id}";
      $pscmd = "psexec -d $cmd 2>&1";
      echo $pscmd.CRLF;
      $line = exec($pscmd, $out);
      print_r($out);
      $this->log("Run cmd $cmd");
      $this->job_pid = preg_reduce("#started with process ID ([0-9]+)#", $line);
      $this->log("Forked with pid {$this->job_pid}");
      return;
    }

    $this->job_pid = pcntl_fork();
    if($this->job_pid == -1)
      throw new Exception("Failed to init fork.");

    if($this->job_pid){
      //Parent
      $this->log("Forked with pid {$this->job_pid}");
      pcntl_wait($status, WNOHANG);
    }else{
      //Child
      $service = yks::$get->config->worker->server['service'];
      job_client::create($this->worker_id, $this->job_id, $service);
    }
  }

  public function stop(){
    $current_pid = getmypid();
    if($current_pid != $this->worker_pid)
      die;

    $this->log("Stopping worker ...");
    $this->running = false;

    //Do not kill watcher on children threads
    if($this->watcher)
      $this->watcher->stop();

    //Kill job
    if($this->job_pid)
      self::pskill($this->job_pid);

    if(file_exists($this->pid_file))
      unlink($this->pid_file);

    exit();
  }

  public static function pskill($pid){
    rbx::ok("Try to kill $pid");

    if(! ($pid = (int) $pid) )
      throw new Exception("Invalid pid");

    if(OS_LINUX)
      posix_kill($pid, SIGTERM);
    else exec("pskill $pid 2>NUL");
  }

  public static function pslist($pid){
    if(! ($pid = (int) $pid) )
      throw new Exception("Invalid pid");

    if(OS_LINUX){
      $cmd = "ps $pid";
      $line = trim(exec($cmd, $out, $exit));
    } else {
      $cmd  = "pslist $pid 2>NUL";
      $line = exec($cmd, $out, $exit);
      list(, $line) = preg_split("#\s+#", $line);

    }
    return starts_with($line, $pid);
  }

  protected function ws($service) {
    $url = sprintf("%s/services/?class=%s&wsdl", yks::$get->config->worker->server['url'], $service);
    return new SoapClient($url);
  }


  private function log($message, $level='info'){
    $str  = "[worker #{$this->worker_id}]";
    if($this->job_id) $str.= "[job #{$this->job_id}]";
    if(OS_WINDOWS)
      exec("title $str");

    self::log_generic($this->log_file, $str.' '.$message, $level);
  }

  public function log_generic($file, $message, $level){

    $line = sprintf('%s [%s][pid:%d]%s', strtoupper($level), date('d/m/Y H:i:s'),  getmypid(), $message);
    if(LOG_CONSOLE)
      echo $line.CRLF;
    file_put_contents($file, $line.CRLF, FILE_APPEND);
  }
}
