<?php

abstract class idaemon {
  private $pid_file;
  private $pid;
  private $parent_path = '';
  
  function __construct($pid_file, $parent_path){
    //Detect os
    define('OS_LINUX', (PHP_OS == 'Linux'));
    define('OS_WINDOWS', !OS_LINUX);
    define('NULL_PIPE', OS_WINDOWS ? "NUL" : "/dev/null");

    //Check pstools
    if(OS_WINDOWS){
      $tools = is_file(cli::which('pslist'));
      $tools &= is_file(cli::which('pskill'));
      $tools &= is_file(cli::which('psexec'));
      if(!$tools){
        $this->trace("Missing pstools");
        exit(1);
      }
    }

    $this->pid_file = $pid_file;
    $this->parent_path = $parent_path;
    $this->pid      = getmypid();
    $this->trace("Current pid #{$this->pid}");

    //Are we in a raw daemon execution ?
    if(cli::$dict['daemon']){
      file_put_contents($this->pid_file, $this->pid);
      $this->run();
      exit(0); // poor man's version
    }
  }

  //Check pid in current pid file
  // is running
  protected function is_running(){
    $running = is_file($this->pid_file);
    $current_pid = (int)file_get_contents($this->pid_file);
    $running &= $current_pid > 0;
    if($current_pid)
      $running &= self::pslist($current_pid);
    return $running;
  }

  protected function trace($msg){
    rbx::ok(date("d/m/Y H:i:s")." : ". $msg);
  }

  protected function abort($msg){
    $this->trace($msg);
    exit(0);
  }

  public function stop(){
  /*
    if(!($pid = $this->is_running()))
      $this->trace("Not running..");
    else posix_kill($pid, SIGTERM);

    if(is_file($this->pid_file))
      unlink($this->pid_file);
    $this->trace("Stop $pid");
  */
  }

  public function daemonize(){
    if($this->is_running()){
      $this->trace("Already running");
      exit(1);
    }
    
    if(OS_LINUX)
      $this->fork_linux();
    else
      $this->fork_windows();
  }
  
  public abstract function run();

  private static function pskill($pid){
    $this->trace("Try to kill $pid");

    if(! ($pid = (int) $pid) )
      throw new Exception("Invalid pid");

    if(OS_LINUX){
      posix_kill($pid, SIGTERM);
    } else {
      exec("pskill $pid 2>NUL");
    }
  }

  private static function pslist($pid){
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
  
  private function fork_linux(){
    if(!OS_LINUX)
      throw new Exception("Invalid os !");

    $child_pid = pcntl_fork();
    if ($$child_pid == -1) {
      $this->trace("Failed to fork !");
      exit(1);
    }
  
    if ($child_pid) {
      //Father dumps pid & die
      $this->trace("Child pid #$child_pid");
      file_put_contents($this->pid_file, $child_pid);
      $this->trace("Father dying...");
      exit(0);

    } else {
      //Child runs
      $this->run();
    }
  }
  
  private function fork_windows(){
    if(!OS_WINDOWS)
      throw new Exception("Invalid os !");

    $cmd = sprintf("psexec -d php %s --daemon 2>&1", $this->parent_path);
    exec($cmd);

    $this->trace("Forked on windows through psexec");
    exit(0);
  }
}