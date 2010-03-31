<?php

abstract class mail_base {
  public $vars_list;
  protected $subject;
  protected $from;
  protected $to = array();
  protected $cc = array();
  protected $dests = array();

  abstract function send($to=false);


  function encode(){
    $str = "";
    $str.= $this->output_headers();
    $str.= $this->first_part->encode();
    
    return $str;
  }

  function split_dest($dests){
    if(is_array($dests))
        return $dests;

    $dests_str = $dests;
    $dests     = array();
    foreach(preg_split("#[;,\n]#",$dests_str) as $line)
        if($line= trim($line)) $dests[]=$line;
    return $dests;
  }

  function to($to){ 
    $to = $this->split_dest($to);
    $this->dests = array_merge($this->dests, $to);
    $this->to = array_merge($this->to, $to);
  }

  function cc($cc){ 
    $cc = $this->split_dest($cc);
    $this->dests = array_merge($this->dests, $cc);
    $this->cc = array_merge($this->cc, $cc);
  }

/**
    Enregistre dans la classe une liste de variable qui seront utilisée pour parametrer les contenus textuels du mail
*/

  function contextualize($vars_list){
    $this->vars_list =  $vars_list;
  }

}