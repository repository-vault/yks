<?php

abstract class mail_base {
  protected $vars_list;
  protected $subject;
  protected $from;
  protected $to = array();
  protected $cc = array();
  protected $dests = array();

  abstract function send($to=false);

/**
* raw : n'applique aucun encoding, ni headers, retourne uniquement le contenu
*/
  function encode($raw = false){
    $str = "";

    if(!$raw)
        $str.= $this->output_headers();

    $str.= $this->first_part->encode($raw);
    
    return $str;
  }

  private function split_dest($dests){
    if(is_array($dests))
        return $dests;

    $dests_str = $dests;
    $dests     = array();
    foreach(preg_split("#[;,\n]#",$dests_str) as $line)
        if($line= trim($line)) $dests[]=$line;
    return $dests;
  }

  private function dest_add($key, $list){

    $list = $this->split_dest($list);
        //stack dests
    $this->dests = array_unique(array_merge($this->dests, $list));

    if(in_array($key, array('to','cc')))
        $this->$key = array_merge($this->$key, $list);//no unique here, thx
  }

  
  public function to($to){ 
    return $this->dest_add("to", $to);
  }

  public function cc($cc){ 
    return $this->dest_add("cc", $cc);
  }

  public function cci($cci){ 
    return $this->dest_add("cci", $cci);
  }

/**
    Enregistre dans la classe une liste de variable qui seront utilisée pour parametrer les contenus textuels du mail
*/

  function contextualize($vars_list){
    $this->vars_list =  $vars_list;
  }

  public function apply_context($contents, $special_chars_decode = true){
    $context = (array) $this->vars_list; 
    $contents = str_evaluate($contents, $context, array(VAR_MASK) );

    if($special_chars_decode) //escape in no longer necessary
        $contents = specialchars_decode($contents);

    return locales_manager::translate($contents);
  }


}