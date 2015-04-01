<?php
/*
$host = new console_host('C:\\Windows\\system32\\netstat.exe -n -p tcp');
$res = $host->exec(); var_dump($res);
sleep(5);
$res = $host->exec(); var_dump($res);
*/

class console_host {
  private $host ;
  private $pipes;
  function __construct($cmd){
    $descriptorspec = array(
       0 => array("pipe", "r"),
       1 => array("pipe", "w"),
       2 => array("pipe", "w"),
    );

    $this->host = proc_open($_SERVER['COMSPEC'], $descriptorspec, $this->pipes);
    stream_set_blocking($this->pipes[1], false);
    $this->cmd = $cmd;
    $this->exec("echo OFF");
  }

  function exec( $cmd = false){
    if(!$cmd) $cmd = $this->cmd;

    if (!is_resource($this->host)) 
      throw new Exception("Host is dead");

    $FINISHED = "FINISHED\r\n";

    $start_cmd = "$cmd\r\n";
    $end_cmd   = "echo $FINISHED";

    fwrite($this->pipes[0], $start_cmd);
    fwrite($this->pipes[0], $end_cmd );

  $output = "";
   while( true ){
      $buffer = fgets($this->pipes[1], 1024);
      if($buffer == $FINISHED) { 
        //fgets($this->pipes[1], 1024); //read blank line
        break;
      } 
      $output .= $buffer;
   }

   $output = substr($output, strlen($start_cmd), -strlen($end_cmd));

    return $output;
  }

  function close(){
    fclose($this->pipes[2]);
    fclose($this->pipes[1]);
    fclose($this->pipes[0]);

    $return_value = proc_close($this->host);
  }

}