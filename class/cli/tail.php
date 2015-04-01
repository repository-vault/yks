<?php


class cli_tail {
  private $file_path = '';

  function __construct($file_path) {
    $this->file_path = $file_path;
    $this->fp = fopen($this->file_path, "r");
    $block = 4096;
    $this->seek(0);
  }

  public function seek($position){
    $this->position = $position;
  }

  function pick_line() {
        clearstatcache ();
      if($this->position == filesize($this->file_path))
        return;
      if($this->position > filesize($this->file_path))
        $this->position = filesize($this->file_path);

      fseek($this->fp, $this->position, SEEK_SET);
      $line = fgets($this->fp);
      
      $this->position = ftell($this->fp);
      return $line;
  }

  public function wait_line($timeout = 1) {
    $timeout = timeout::from_seconds($timeout);
    do {
        $line = $this->pick_line();
        if($line) return $line;
        $timeout->sleep(0.3);
    } while(!$timeout->expired);
  }

  public function tail($lines = 10)  {
    do {
      usleep(1000* 1000 * 0.2);
      $line = $this->pick_line();
      echo $line;
    } while(true);
  }

  public function close(){
    fclose($this->fp);
  }
}
