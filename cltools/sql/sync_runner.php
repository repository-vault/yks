<?
/**
* This runner help you using sql_sync using classic configuration file
* http://doc.exyks.org/wiki/Source:cltools/sql/sync_runner
*/
class sync_runner {

  function __construct(){
    $this->sync = config::retrieve("sync");
    $this->sources = array();
    foreach($this->sync->sources->source as $source)
        $this->sources[(string)$source['key']]  = attributes_to_assoc($source);

    $this->from = $this->sources[(string) $this->sync['from']];
    $this->to   = $this->sources[(string) $this->sync['to']];

    $this->patterns = array();
    foreach($this->sync->patterns->pattern as $pattern)
        $this->patterns[(string)$pattern['key']] = $pattern;

    cli::box("Available patterns", array_keys($this->patterns));
  }
  
  function start($pattern){
    $pattern = $this->patterns[$pattern];
    if(!$pattern)
        throw new Exception("Invalid pattern key");


    $runner = new sql_sync((string)$pattern->tables, $this->from, $this->to);
    interactive_runner::start($runner);
  }
  

}
