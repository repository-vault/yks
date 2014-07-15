<?php

class interactive_runner {
  private $obj;
  private $className;
  private $commands_list;
  private $current_command_completion = null;

  private $command_pipe;
  private $file;
  private $magic_call; //does current object support __call ?
  private $static; //static mode

  const ns = "runner";

  static function init(){
    if(!classes::init_need(__CLASS__)) return;

    classes::register_class_path("doc_parser", CLASS_PATH."/apis/doc/parse.php");
  }

  function __construct($from, $args = array()){
    $this->file = getcwd().DIRECTORY_SEPARATOR.$GLOBALS['argv'][0];


    $this->reflection_scan($this, self::ns); //register runners own commands


    $this->obj  = null;
    $this->static = cli::$dict['ir://static'];

    if(is_string($from)) {
        $this->className = $from;
        if($this->static)
          $this->obj = $from; //static mode
    } else {
        $this->obj       = $from;
        $this->className = get_class($this->obj);
    }

    if(is_a($this->obj, 'SoapClient')) {

        $reflector = $this->reflection_scan_wsdl($this->obj, $this->className);
    } else {
        $reflector = $this->reflection_scan($this->obj, $this->className, $this->className);
    }

    $mode_str = is_null($this->obj) ? "auto-instanciation" : "existing object";
    rbx::ok("Runner is ready '{$this->className}' in $mode_str mode");

    if(is_null($this->obj)) {
      $instanciate = $reflector->hasMethod('instanciate') && ( !$reflector->IsInstantiable()  || cli::$dict['ir://instanciate']);

      if($instanciate)
        $this->obj = call_user_func_array(array($this->className, 'instanciate'), $args);
      else
        $this->obj = $reflector->IsInstantiable() ? ($args ? $reflector->newInstanceArgs($args) : $reflector->newInstance() ) : $this->className;
    }

    cli::register_completion(array($this, 'completion_function'));
  }

  /**
   * @interactive_runner disable
   */
  public function completion_function($input, $index){
    $infos = readline_info();
    $completion = array();

    if(!$index && !$this->current_command_completion){
      foreach ($this->commands_list as $command) {
        if($command['command_ns'] == $this->className){
          $completion[] = $command['command_key'];
        }
      }
    }
    else {
      $line = pick($this->current_command_completion, substr($infos['line_buffer'], 0, $infos['end']) );
      $args = cli::parse_args($line);
      $method_name   = array_shift($args);
      $command_infos = $this->lookup($method_name);
      if(!$command_infos)
        return false;
      $command_args    = array_keys(array_msort($command_infos['usage']['params'], array('position' => SORT_ASC)));
      $arg_name        = $command_args[max(0, (count($args) - (substr($line, -1) == " " ? 0 : 1)))];

      if($completion_callback = $command_infos['usage']['params'][$arg_name]['completion_callback']) 
        $completion = call_user_func($completion_callback, $args, $this->obj);
      else if($completion_values      = $command_infos['usage']['params'][$arg_name]['completion_values']) 
        $completion = $completion_values;
      else 
        $completion = array();
    }

    $completion = array_filter($completion);

    return empty($completion) ? array("") : $completion;
  }


/**
* show available commands
* @autocomplete command interactive_runner::get_commands_list
* @alias ?
*/
  function help( $command = null){
    if($command_infos = $this->lookup($command)) {
      cli::box($command, join(LF, $command_infos['usage']['doc']));
      return;
    }

    $msgs = array();

    foreach($this->commands_list as $command_hash=>$command) {
      if($command['usage']['hide']) continue;
      $str = "{$command['command_key']} ";
      $parametred_aliases = array_filter($command['aliases']);

      $aliases  = array_keys(array_diff_key($command['aliases'], $parametred_aliases));
      $aliases  = array_diff($aliases, array($command['command_key'], $command_hash));
      if($aliases) $str.= "(".join(', ', $aliases).") ";

      if($command['usage']['params']) {
        $tmp_trailing_optionnal = 0; $tmp_str = array();
        foreach($command['usage']['params'] as $param_name=>$param_infos){
            $tmp_str [] = (isset($param_infos['optional']) ? '[':'')."\$$param_name";
            if(isset($param_infos['optional'])) $tmp_trailing_optionnal++;
        }
        $str .= join(', ', $tmp_str).str_repeat("]", $tmp_trailing_optionnal);
      }

      if($doc = $command['usage']['doc'])
        $str = $str . str_repeat(" ", max(1, cli::$cols - strlen($str) - strlen($doc[0]) - 2)).$doc[0];

      $msgs[$command['command_ns']][] = $str;

      foreach($parametred_aliases as $alias_name=>$args)
        $msgs[$command['command_ns']][] = "$alias_name (={$command['command_key']} ".join(" ", $args).")";


    }

    $rbx_msgs = array();
    foreach($msgs as $command_ns=>$msgs) {
        //title
      $rbx_msgs[] = $command_ns == $this->className ? "Commands list" : "From $command_ns";
      $rbx_msgs[] = join(LF, $msgs);
    }

    call_user_func_array(array('rbx', 'box'), $rbx_msgs);
  }

/**
* replay last command
* @alias r
*/
  function replay(){
    //this is only a placeholder
  }


/**
* @interactive_runner hide
*/
  function register_alias($command_key, $alias_name, $args = array()){
    $this->command_aliases($this->className, $command_key, array($alias_name=>$args) );
  }

  private function generate_command_hash($command_ns, $command_key){
    return "$command_ns::$command_key";
  }

  private function lookup($command_prompt){
    $command_resolve = array();

    foreach($this->commands_list as $command_hash=>$command_infos)
      if(isset($command_infos['aliases'][$command_prompt]))
        $command_resolve[] = $command_infos;

    if(count($command_resolve) > 1)
      throw rbx::error("Too many results for command '*::$command_prompt', please specify ns");

    return first($command_resolve);
  }

  private function command_aliases($command_ns, $command_key){
    $command_hash = $this->generate_command_hash($command_ns, $command_key);
    if(!isset($this->commands_list[$command_hash]))
      return false;

    $aliases_list = &$this->commands_list[$command_hash]['aliases'];
    $aliases = func_get_args(); $aliases = array_slice($aliases, 2);

    foreach($aliases as $alias){
        if(!is_array($alias)) $alias = array($alias=>false);
        $aliases_list = array_merge($aliases_list, $alias);
    }

    return true;
  }
  private function command_register($command_ns, $command_key, $callback, $usage){
    $command_hash = $this->generate_command_hash($command_ns, $command_key);
    $this->commands_list[$command_hash] = array(
      'command_ns'  => $command_ns,
      'command_key' => $command_key,
      'usage'       => $usage,
      'callback'    => $callback,
      'aliases'     => array(),
    );

    $this->command_aliases($command_ns, $command_key, $command_key, $command_hash);
  }

  private function command_parse($command_prompt, $command_args = array(), $command_dict = array()) {

      if(!$command_prompt)
        throw new Exception("No command");

      $command_infos = $this->lookup($command_prompt);

      if(!$command_infos) {
        if($this->magic_call)
          return array(array($this->obj, $command_prompt), $command_args);
        else throw rbx::error("Invalid command key '$command_prompt'");
      }

      $alias_args        = $command_infos['aliases'][$command_prompt];

      if(is_array($alias_args))
          $command_args  = array_merge($alias_args, $command_args);

      $command_args_mask = $command_infos['usage']['params'];

      $param_id = 0; $args = array();
      foreach($command_args_mask as $param_name=>$param_infos){

        if(array_key_exists($param_id, $command_args)){
          $param_in = $command_args[$param_id];
        }
        else{
          if(array_key_exists($param_name, $command_dict)){
            $param_in = $command_dict[$param_name];
          }
          else {
            if(array_key_exists('default', $param_infos)){
             $param_in = $param_infos['default'];
            }
            else {
              $this->current_command_completion = join(' ', $this->last_command).' ';
              $param_in = cli::text_prompt("\${$this->className}[{$param_name}]", null);
              $this->current_command_completion = null;
            }
          }
        }

        $args[] = $param_in;
        $param_id++;
      }
      cli::add_history($command_prompt.' '.join(' ', $args));

      return array($command_infos['callback'], $args);
  }

/**
* ends interactive session
* @alias q
* @alias exit
*/
  function quit(){
    $this->command_pipe = SIGTERM;
    rbx::ok("Quit");
  }


/**
* @interactive_runner disable
* runner's internal looop, signal management
*/
  protected function run() {
    $run = $start = array();

    if(!empty(cli::$dict['ir://run']))
      $run = cli::$dict['ir://run'];
    else if(!empty(cli::$dict['ir://start']))
      $start = cli::$dict['ir://start'];

    if($run || $start) {
      if($run === true)
        $run = 'run';

      if($start && !is_array($start)) $start = array($start);
      if($run  && !is_array($run)) $run = array($run);

      foreach(array_merge($start, $run) as $cmd) {
        list($command_callback, $command_args) = $this->command_parse($cmd, array(), cli::$dict);
        $res = call_user_func_array($command_callback, $command_args);
        if($res !== null)
          cli::box('Response', $res);
      }

      if($run)
        exit();
    }


    for(;;) {
      $this->command_loop();
      if($this->command_pipe == SIGTERM)
        return;
    }
  }

  private $last_command = array();
  private static $REPLAY_COMMAND = array('r');

    //embeded object loop, deal with commands
  private function command_loop(){
   ///system("stty -icanon");

    while(is_null($this->command_pipe)){

      try {
        $command_split = array();
        cli::text_prompt('$'.$this->className, null, $command_split);
        if($command_split == self::$REPLAY_COMMAND)
            $command_split = $this->last_command;
        else
            $this->last_command = $command_split;

        $command_prompt  = array_shift($command_split);
        list($command_callback, $command_args) = $this->command_parse($command_prompt, $command_split);
      } catch(Exception $e){ continue; }

      try {
        $res =  call_user_func_array($command_callback, $command_args);
        if($res !== null)
            cli::box("Response", $res);

      } catch(Exception $e){
        echo CRLF;
        $msg = $e->getMessage() ? $e->getMessage()  : $e;
        $trace = $e->getTraceAsString();
        $trace = strtr($trace, array(YKS_PATH=>'yks', getcwd().DIRECTORY_SEPARATOR => ''));
        cli::box("!! Uncatched exception !!", $msg , "trace", $trace, array('trim'));
        continue;
      }

    }

  }


  private function reflection_scan_wsdl($instance, $command_ns){
    $methods = $instance->__getFunctions();
    foreach($methods as $method_sign){
        $out = null;
        if(!preg_match("#(.*?)\s+(.*?)\((.*?)\)#", $method_sign, $out)) continue;
        list(,$method_type, $method_name, $method_args_str) = $out;

        $method_args = array();
        if(preg_match_all('#([^\s]*)\s+\$([^,\s]*)#', $method_args_str, $out)) {
            $method_args = array_combine($out[2], $out[1]);
        }

        $callback = array($instance, $method_name);
        $usage = array('params'=>array());
        foreach($method_args as $arg_name => $arg_type)
            $usage['params'][$arg_name] = array('type' => $arg_type);

        $this->command_register($command_ns, $method_name, $callback, $usage);
    }

  }


/**
* Return ReflectionClass
* @interactive_runner hide
*/
  function reflection_scan(&$instance, $command_ns = null, $className = null){
    if(is_null($instance) && !$className)
      throw new Exception("Cannot scan unknown static class");

    if(!$className) 
      $className = get_class($instance);

    if(!$command_ns) $command_ns = $className;
    $reflect   = new ReflectionClass($className);
    $methods   = $reflect->getMethods();

    foreach($methods as $method) { $method_name = $method->getName();

      $is_command = false;
      $callback   = null;
      $is_magic   = starts_with($method_name, "__");

      if($method_name == "__call" && !$this->static)
        $this->magic_call = true;

      if($method->isPublic()
          && !$method->isStatic()
          && !$is_magic
          && !$method->isConstructor()
          && !$this->static ) {
        $callback = array(&$instance, $method_name);
      } elseif($method->isPublic()
          && $method->isStatic()
          && !$is_magic
          && $method_name != "init"){
        $callback = array($className, $method_name);
      } else {
        continue;
      }

      $command_key = $method_name;
      $params = $method->getParameters();
      $doc = doc_parser::parse($method->getDocComment());

      $tmp = $doc['args']['interactive_runner']['computed'];
      if(!$tmp) $tmp = array();
      if(in_array("disable", $tmp))
        continue;

      $usage = array('params'=>array(), 'doc' => $doc['doc'], 'hide' => in_array("hide", $tmp));

      foreach($params as $param) {
        $param_infos = array(
          'position'   => $param->getPosition(),
          'completion' => array(),
        );
        if($param->isOptional()){
          $param_infos['optional'] = true;
          $param_infos['default']  = $param->getDefaultValue();
        }
        $usage['params'][$param->getName()] = $param_infos;

      }
      $this->command_register($command_ns, $command_key, $callback, $usage);
      $command_hash = $this->generate_command_hash($command_ns, $command_key);

      if($autocompletes = $doc['args']['autocomplete']['values']){
        foreach($autocompletes as $values){
          $arg_name  = strip_start(array_shift($values), '$');
          if(!isset($this->commands_list[$command_hash]['usage']['params'][$arg_name]))
            continue;
          $callback  = preg_match("#^([a-z0-9_]+)::([a-z0-9_]+)$#", first($values), $out) && count($values) == 1;
          if($callback)
            $this->commands_list[$command_hash]['usage']['params'][$arg_name]['completion_callback'] = array($out[1] == 'self' ? $className : $out[1], $out[2]);
          else
            $this->commands_list[$command_hash]['usage']['params'][$arg_name]['completion_values'] = $values;
        }
     }

      if($aliases = $doc['args']['alias']['values']) foreach($aliases as $args) {
        $alias_name  = array_shift($args);
        if(!( $alias_name && $command_key)) continue;
        $this->command_aliases($command_ns, $command_key, array($alias_name=>$args) );
      }
    }

    return $reflect;
  }


  /**
* toggle fullsize mode
* @interactive_runner hide
* @alias fs
*/
  function fullsize(){
    if(cli::$OS == cli::OS_WINDOWS) {
      $cmd = "mode CON";
      exec($cmd, $out);
      if( preg_match_all("#(lines|columns):\s+([0-9]+)$#mi", join("\n", $out), $out))
        $size = array_combine(array_map('strtolower', $out[1]), $out[2]);
      cli::$cols = $size['columns'] ? $size['columns'] - 1 : cli::$cols;
    } else
      cli::$cols = trim(`tput cols`);

    $this->help();
  }



  private static $current_runner = null;

/**
* @interactive_runner disable
*/
  static public function start($obj, $args = array()) {

    if(isset(cli::$dict['ir://output']))
      rbx::$output_mode = cli::$dict['ir://output'];


    if(!is_array($args))
      $args = array($args);
    self::$current_runner = new self($obj, $args);

    if(!empty(cli::$dict['ir://fs']))
      self::$current_runner->fullsize();
    else
      self::$current_runner->help();

    self::$current_runner->run(); //private internal
  }

    //helpers for interactive runner completion
  private static function get_commands_list(){ return array_keys(self::$current_runner->commands_list); }

}
