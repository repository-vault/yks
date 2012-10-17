<?

class cli_tail {
  private $file_path = '';

  function __construct($file_path) {
    $this->file_path = $file_path;
    $this->fp = fopen($this->file_path, "r");
    $block = 4096;
    $this->position = 0;
  }

  public function pick_line() {
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