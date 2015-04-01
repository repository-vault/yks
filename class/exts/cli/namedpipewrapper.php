<?php


abstract class namedpipewrapper {
  var $pipe_file = "/dev/null";
  var $ping_file = "/tmp/syslog_mail_ping";

  protected abstract function callback($msg);

  function __construct($pipe_file){
    $this->pipe_file = $pipe_file;
  }

  private function run(){

   do {
    $fp = fopen($this->pipe_file, 'r');

    while($line = fgets ($fp, 1024)){

      if($line === false)
        die("Error ?");

      if(trim($line) == "ping") {
        $this->pong();
        continue;
      }
      $this->callback($line);
    }

   } while(true);

  }

  function install(){
    if($this->installed)
      throw rbx::error("Named pipe file already present");
    posix_mkfifo($this->pipe_file, 0600);
//    chmod($this->pipe_file, 0622);
    if(!$this->installed)
        throw rbx::error("Could not install named pipe in {$this->pipe_file}");
    rbx::ok("Named pipe installed in {$this->pipe_file}");
  }

  function test(){
    if(!$this->installed)
      throw rbx::error("Not properly installed");

    $fp = fopen($this->pipe_file, 'w');
    stream_set_blocking($fp, false);
    stream_set_timeout($fp, 1);
    fwrite($fp, "ta");
  }

  function remove(){
    @unlink($this->pipe_file);
    rbx::ok("Named pipe removed");
  }

  function start(){
    if(!$this->installed)
      throw rbx::error("Not properly installed");

    if($this->ping())
      rbx::ok("Already running !!");
    else  {
      rbx::ok("Not running, start now");
      $this->run();
    }
  }

/** ping/pong **/
  private function pong(){
    file_put_contents($this->ping_file, "pong");
  }

  private function ping(){
    if(is_file($this->ping_file)) unlink($this->ping_file);
    $cpid = pcntl_fork();
    if ($cpid == -1) {
         die('could not fork');
    } else if ($cpid) {
         // we are the parent
         $out = timeout::from_seconds(1); $success = false;
         do {
            $success = @file_get_contents($this->ping_file) == "pong";
         } while(!$success && $out->sleep(0.1) && !$out->expired );

         if(!$success) {
            posix_kill($cpid, SIGTERM);
          } else {
            return true;
          }
            
         pcntl_wait($status); //Protect against Zombie children
    } else {
         file_put_contents($this->pipe_file, "ping\n");
         die;//end execution here
    }
  }

/** Helpers **/
  private function get_installed(){
    clearstatcache();
    return file_exists($this->pipe_file) && filetype($this->pipe_file) == 'fifo';
  }

  function __get($key){
    if(method_exists($this, $getter="get_$key"))
      return $this->$getter();
  }

}
