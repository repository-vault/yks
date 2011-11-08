<?php

class interactive_runner {
  private $obj;
  private $className;
  private $commands_list;

  private $command_pipe;
  private $file;
  private $magic_call; //does current object support __call ?
  const ns = "runner";
  
  static function init(){
    if(!classes::init_need(__CLASS__)) return;

    classes::register_class_path("doc_parser", CLASS_PATH."/apis/doc/parse.php");
  }

  private function __construct($from, $args = null){
    $this->file = getcwd().DIRECTORY_SEPARATOR.$GLOBALS['argv'][0];
    $this->obj  = null;

    if(is_string($from)) {
        $this->className = $from;
    } else {
        $this->obj       = $from;
        $this->className = get_class($this->obj);
    }

    if(is_a($this->obj, 'SoapClient')) {

        $reflector = $this->reflection_scan_wsdl($this->obj, $this->className);
    } else {
        $reflector = $this->reflection_scan($this->className, $this->className, $this->obj);
    }

    $mode_str = is_null($this->obj) ? "auto-instanciation" : "existing object";
    rbx::ok("Runner is ready '{$this->className}' in $mode_str mode");

    $this->reflection_scan(__CLASS__, self::ns, $this); //register runners own commands

    $this->help();

    if(is_null($this->obj))
        $this->obj = is_null($args)
                     ? $reflector->newInstance()
                     : $reflector->newInstanceArgs($args);
  }


/**
* @alias ?
*/
  function help(){
    $msgs = array();

    foreach($this->commands_list as $command_hash=>$command) {

      $str = "{$command['command_key']} ";
      $parametred_aliases = array_filter($command['aliases']);

      $aliases  = array_keys(array_diff_key($command['aliases'], $parametred_aliases));
      $aliases  = array_diff($aliases, array($command['command_key'], $command_hash));
      if($aliases) $str.= "(".join(', ', $aliases).") ";

      if($command['usage']['params']) {
        $tmp_trailing_optionnal = 0; $tmp_str = array();
        foreach($command['usage']['params'] as $param_name=>$param_infos){
            $tmp_str [] = ($param_infos['optional'] ? '[':'')."\$$param_name";
            if($param_infos['optional']) $tmp_trailing_optionnal++;
        }
        $str .= join(', ', $tmp_str).str_repeat("]", $tmp_trailing_optionnal);
      }

      $msgs[$command['command_ns']][] = $str;

      foreach($parametred_aliases as $alias_name=>$args)
        $msgs[$command['command_ns']][] = "$alias_name (={$command['command_key']} ".join(" ", $args).")";

    }
    $msgs[self::ns][] = "r replay last command";

    $rbx_msgs = array();
    foreach($msgs as $command_ns=>$msgs) {
        //title
      $rbx_msgs[] = $command_ns == $this->className ? "Commands list" : "From $command_ns";
      $rbx_msgs[] = join(LF, $msgs);
    }

    call_user_func_array(array('rbx', 'box'), $rbx_msgs);
  }

  private function command_aliases($command_ns, $command_key){
    $command_hash = "$command_ns::$command_key";
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
    $command_hash = "$command_ns::$command_key";
    $this->commands_list[$command_hash] = array(
      'command_ns'  => $command_ns,
      'command_key' => $command_key,
      'usage'       => $usage,
      'callback'    => $callback,
      'aliases'     => array(),
    );

    $this->command_aliases($command_ns, $command_key, $command_key, $command_hash);
  }

  function php($cmd){
    $args = func_get_args(); $args = join(' ', $args);
    rbx::ok("Exec $args");
    eval($args);
  }
 
  private function command_parse($command_args) {
      $command_prompt  = array_shift($command_args);
      $command_resolve = array();
      foreach($this->commands_list as $command_hash=>$command_infos)
        if(isset($command_infos['aliases'][$command_prompt]))
          $command_resolve[] = $command_hash;

      if(!$command_prompt)
        throw new Exception("No command");

      if(!$command_resolve) {
        if($this->magic_call)
          return array(array($this->obj, $command_prompt), $command_args);
        else throw rbx::error("Invalid command key '$command_prompt'");
      }

      if(count($command_resolve) > 1)
        throw rbx::error("Too many results for command '*::$command_prompt', please specify ns");

      $command_hash      = $command_resolve[0];
      $command_infos     = $this->commands_list[$command_hash];
      $alias_args        = $command_infos['aliases'][$command_prompt];

      if(is_array($alias_args))
          $command_args  = array_merge($alias_args, $command_args);

      $command_args_mask = $command_infos['usage']['params'];

      $param_id = 0; $missing = array();
      foreach($command_args_mask as $param_name=>$param_infos){
        $param_in = $command_args[$param_id++];
        if(!$param_in && !$param_infos['optional'])
            $missing[] = $param_name;
      }

      if($missing) {

        foreach($missing as $param_name) {
            $param_value = cli::text_prompt("\${$this->className}[{$param_name}]");
            $command_args[] = trim($param_value);
        }
      }

      return array($command_infos['callback'], $command_args);
  }

/**
* @alias q
* @alias exit
*/
  function quit(){
    $this->command_pipe = SIGTERM;
    rbx::ok("Quit");
  }

    //runner's internal looop, signal management
  private function main_loop(){

    while(true){ 

      $this->command_loop();

      if($this->command_pipe == SIGTERM)
        return;

    }

  }
  
  private $last_command;
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
        
        list($command_callback, $command_args) = $this->command_parse($command_split);
      } catch(Exception $e){ continue; }


      try {
        $res =  call_user_func_array($command_callback, $command_args);
        if($res !== null)
            cli::box("Response", $res);

      } catch(Exception $e){
        echo CRLF;
        $msg = $e->getTraceAsString();
        $msg = strtr($msg, array(YKS_PATH=>'yks', getcwd().DIRECTORY_SEPARATOR => ''));
        cli::box("!! Uncatched exception !!", $e->getMessage(), "trace", $msg, array('trim'));
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
*  Return ReflectionClass
*/
  private function reflection_scan($className, $command_ns, &$instance){
    $reflect   = new ReflectionClass($className);
    $methods   = $reflect->getMethods();

    foreach($methods as $method) { $method_name = $method->getName();

      $is_command = false;
      $callback   = null;
      $is_magic   = starts_with($method_name, "__");
      if($method_name == "__call") $this->magic_call = true;
      if($method->isPublic()
          && !$method->isStatic()
          && !$is_magic
          && !$method->isConstructor()) {
        $callback = array(&$instance, $method_name);
      } elseif($method->isPublic()
          && $method->isStatic()
          && $method_name != "init"){
        $callback = array($className, $method_name);
      } else {
        continue;
      }

      $command_key = $method_name;
      $usage = array('params'=>array());
      $params = $method->getParameters();
      $doc = doc_parser::parse($method->getDocComment());

      $tmp = $doc['args']['interactive_runner']['computed'];
      if(is_array($tmp) && in_array("hide", $tmp)) 
        continue;
        
      foreach($params as $param) {
        $param_infos = array();
        if($param->isOptional()) $param_infos['optional'] = true;

        $usage['params'][$param->getName()] = $param_infos;

      }

      $this->command_register($command_ns, $command_key, $callback, $usage);

      if($aliases = $doc['args']['alias']['values']) foreach($aliases as $args) {
        $alias_name  = array_shift($args);
        if(!( $alias_name && $command_key)) continue;
        $this->command_aliases($command_ns, $command_key, array($alias_name=>$args) );
      }

    }

    return $reflect;
  }

/**
* @interactive_runner hide
*/
  static public function start(){//$obj, $args
    $args = func_get_args();
    $obj = array_shift($args);
    $runner = new self($obj, count($args) !=0 && $args != array(null) ? $args : null);
    $runner->main_loop(); //private internal
  }
}

