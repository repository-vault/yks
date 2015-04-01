<?php

class stdlogdispatch extends namedpipewrapper {
  private $messages = array();

  function callback($str){
    $mask = "#^[A-Za-z]+ [0-9 :]+ [a-z0-9_-]+ [a-z0-9_-]+\[[0-9]+\]: \[error\] \[client [0-9.]+\]\s+(.*)#";

    $low  = floor(time()/1200); //low is 20 min 
    $high = floor(time()/120);  //high is 2 min 

    if(!preg_match($mask, $str, $out))
      $msg = "Unknow message";
    else $msg = $out[1];

    if(preg_match("#^PHP Warning:#", $msg))
      return;

    if(preg_match("#^File does not exist:#", $msg)) //do 404 ?
      return;

    if(preg_match("#^PHP (Fatal|Parse) error:#", $msg))
      return $this->notify("$msg $high", $str);

    $this->notify("$msg $low", $str);
  }


  private function notify($hash, $str){
    if(isset($this->messages[$hash]))
      return;

    $now = time(); $expire = 86400;
    $this->messages[$hash] = array($now);
    foreach($this->messages as $hash=>$infos)
      if($now - $infos[0] > $expire) unset($this->messages[$hash]);

    mail($this->sendto, "Error from server", $str);
    syslog(LOG_INFO, "Notify {$this->sendto} of $str".print_r($this->messages,1));
    
    
  }

}