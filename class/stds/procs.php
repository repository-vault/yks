<?php

class procs {

  function exec_node($str, $json_cmd = false){
    if($json_cmd)
      $str = "process.stdout.write(JSON.stringify($str));";

    $node = cli::which('node');

    $contents = self::stdin_call($node, $str);

    if($json_cmd)
      $contents = json_decode($contents, true);

    return $contents;
  }

  function stdin_call($exe, $stdin){

    $descriptorspec = array(
        0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
        1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
        2 => array("pipe", "w") // stderr is a pipe to write to
    );

    $options = array('suppress_errors' => true, 'binary_pipes' => true, 'bypass_shell' => true);

    $process = proc_open($exe, $descriptorspec, $pipes, null, $_ENV, $options);
    if(!$process)
     throw new Exception("Cannot start node");

    $w= fwrite($pipes[0], $stdin);
    fclose($pipes[0]); //close stdin, node start

    $contents = stream_get_contents($pipes[1]); //read until end
    fclose($pipes[1]);
    $return_value = proc_close($process);


    return $contents;
  }

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