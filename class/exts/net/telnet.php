<?

class telnet {

  function run($ip, $port){

    $socket = fsockopen($ip, $port);

    if(!$socket)return;
    stream_set_blocking($socket, 0);
    stream_set_blocking(STDIN, 0);

    do {
      echo "$ ";
      $read   = array( $socket, STDIN); $write  = NULL; $except = NULL;
      
      if(!is_resource($socket)) return;
      $num_changed_streams = @stream_select($read, $write, $except, null);
      if(feof($socket)) return ;
      

      if($num_changed_streams  === 0) continue;
      if (false === $num_changed_streams) {
          /* Error handling */
        var_dump($read);
        echo "Continue\n";
        die;
      } elseif ($num_changed_streams > 0) {
        echo "\r";
        $data = fread($socket, 4096);
        if($data !== "") 
          echo "<<< $data";

        $data2 = fread(STDIN, 4096);

        if($data2 !== "") {
          echo ">>> $data2";
          fwrite($socket, $data2);
        }
      }

    } while(true);

  }
}
