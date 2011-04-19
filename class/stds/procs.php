<?

class procs {
  public static function exec_all_pipes($exe, $args) {
    //$proc = proc_open($cmd, array(STDIN, STDOUT, STDOUT), $null); //!not working ?!
    $cmd = $exe." ".$args;

    rbx::ok($cmd);
    $pipe_specs = array(
       0 => array("pipe", "r"), //stdin
       1 => array("pipe", "w"), //stdout
       2 => array("pipe", "w")  //stderr
    );
    $proc = proc_open($cmd, $pipe_specs, $pipes);
    $read = array($pipes[1], $pipes[2]); $write = null; $except = null;
    $out = "";
    do {
      $status = proc_get_status($proc);
      if(!$status['running'])
        break;
      $select = stream_select($read, $write, $except, 1);
      if($select === false)
        throw new Exception("Could not read stream");
      if($select === 0) continue;
      foreach($read as $stream) $out.= stream_get_contents($stream);
    } while(true);
    return $out;
  }
}