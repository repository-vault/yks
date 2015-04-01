<?php
/**
* This runner help you using sql_sync using classic configuration file
* http://doc.exyks.org/wiki/Source:cltools/sql/sync_runner
*/
class sync_runner {

  function __construct($pattern = false){

    $this->sync = yks::$get->config->sync;
    $this->sources = array();
    foreach($this->sync->sources->source as $source)
        $this->sources[(string)$source['key']]  = attributes_to_assoc($source);

    $this->from = $this->sources[(string) $this->sync['from']];
    $this->to   = $this->sources[(string) $this->sync['to']];

    $this->patterns = array();
    foreach($this->sync->patterns->pattern as $pattern_xml)
        $this->patterns[(string)$pattern_xml['key']] = $pattern_xml;

    if(!$pattern)
        cli::box("Available patterns", array_keys($this->patterns));
    else $this->start($pattern);
  }
  
  function start($pattern){
    $pattern = $this->patterns[$pattern];
    if(!$pattern)
        throw new Exception("Invalid pattern key");


    $runner = new sql_sync((string)$pattern->tables, $this->from, $this->to);
    interactive_runner::start($runner);
  }
  

}
