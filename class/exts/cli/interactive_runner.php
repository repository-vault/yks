<?php

class interactive_runner {
  private $obj;
  private $className;
  private $commands_list;
  private $current_command_completion = null;


  const LOOP_IDL  = 'LOOP_IDL';  //loop not running
  const LOOP_RUN  = 'LOOP_RUN';  //loop running
  const LOOP_QUIT = 'LOOP_QUIT'; //loop end

  private $command_pipe;
  private $file;
  private $output; //output mode,  see below

  public $current_call; //read only structure with current call args

  const ns = "runner";

  const OUTPUT_RBX  = 'rbx';
  const OUTPUT_JSON = 'json';
  const OUTPUT_RAW  = 'raw';

  static function init(){
    if(!classes::init_need(__CLASS__)) return;

    classes::register_class_path("doc_parser", CLASS_PATH."/apis/doc/parse.php");
  }

  public function __construct(){
    $this->output = self::OUTPUT_RBX;

    if(isset(cli::$dict['ir://raw'])) {
      $this->output = self::OUTPUT_RAW;
    }
    if(isset(cli::$dict['ir://json'])) {
      $this->output = self::OUTPUT_JSON;
    }

    if(isset(cli::$dict['ir://output'])) {
      if(!bool(cli::$dict['ir://output'])) $this->output = self::OUTPUT_RAW;
      elseif(cli::$dict['ir://output'] === true) $this->output = self::OUTPUT_RBX;
      else $this->output = cli::$dict['ir://output'];
    }

    rbx::$output_mode = $this->output == self::OUTPUT_RBX;

    $this->file = getcwd().DIRECTORY_SEPARATOR.$GLOBALS['argv'][0];
    cli::register_completion(array($this, 'completion_function'));

    $this->reflection_scan($this, self::ns); 
  }

  /**
   * @interactive_runner disable
   */
  public function completion_function($input, $index){
    $infos = readline_info();
    $completion = array();

    if(!$index && !$this->current_command_completion){
      foreach ($this->commands_list as $command) {
        if($command['command_ns'] != self::ns){
          $completion[] = $command['command_key'];
        }
      }
    }
    else {
      $line = pick($this->current_command_completion, substr($infos['line_buffer'], 0, $infos['point']) );
      $args = cli::parse_args($line);
      $method_name   = array_shift($args);
      $command_infos = $this->lookup($method_name);
      if(!$command_infos)
        return false;

      $command_args    = array_keys(array_msort($command_infos['usage']['params'], array('position' => SORT_ASC)));
      $arg_index       = pick($this->mandatory_arg_index, max(0, (count($args) - (substr($line, -1) == " " ? 0 : 1))) );
      $arg_name        = $command_args[$arg_index];
      if(!$this->mandatory_arg_index && count($args))
        foreach($command_args as $k=>$v)
          $this->current_call['args'][$v] = array_get($args, $k);

      if($completion_callback = $command_infos['usage']['params'][$arg_name]['completion_callback'])
        $completion = call_user_func($completion_callback, array_get($args, $arg_index), $completion_callback[0], $this, $args);
      else if($completion_values  = $command_infos['usage']['params'][$arg_name]['completion_values'])
        $completion = $completion_values;
      else
        $completion = array();
    }

    $completion = array_filter($completion);

    return empty($completion) ? array("") : $completion;
  }



  private function help_cmd($command){
    if($command['usage']['cli'])
      return "(none) -- ONLY AVAILABLE THROUGH CLI";

    $str = "{$command['command_key']} ";
    $parametred_aliases = array_filter($command['aliases']);

    $aliases  = array_keys(array_diff_key($command['aliases'], $parametred_aliases));
    $aliases  = array_diff($aliases, array($command['command_key'], $command['command_hash']));
    if($aliases) $str.= "(".join(', ', $aliases).") ";

    if($command['usage']['params']) {
      $tmp_trailing_optionnal = 0; $tmp_str = array();
      foreach($command['usage']['params'] as $param_name=>$param_infos){
          $tmp_str [] = (isset($param_infos['optional']) ? '[':'')."\$$param_name";
          if(isset($param_infos['optional'])) $tmp_trailing_optionnal++;
      }
      $str .= join(', ', $tmp_str).str_repeat("]", $tmp_trailing_optionnal);
    }
    return $str;
  }


/**
* @alias y true
* @alias n false
* @interactive_runner hide
*/
  function yes_mode($mode = true){
     cli::$unattended = bool($mode);
     rbx::ok("Unattended mode is ".(cli::$unattended ? "on" : "off"));
  }

/**
* @alias ?
* @interactive_runner hide
*/
  function list_commands(){
    $msgs      = array();
    $rbx_msgs = array();

    foreach($this->commands_list as $command_hash=>$command) {
      if($command['usage']['hide']) continue;
      if(!$command['usage']['cli']) $str = $this->help_cmd($command);
      else $str = "{$command['command_key']} (cli-only)";

      if(isset($command['usage']['doc'][0]) && $doc = trim($command['usage']['doc'][0]))
        $str = $str . str_repeat(" ", max(1, cli::$cols - strlen($str) - strlen($doc) - 2)).$doc;

      $msgs[$command['command_ns']][] = $str;

      $parametred_aliases = array_filter($command['aliases']);

      foreach($parametred_aliases as $alias_name=>$args)
        $msgs[$command['command_ns']][] = "$alias_name (={$command['command_key']} ".join(" ", $args).")";
    }

    foreach($msgs as $command_ns=>$msgs) {
        //title
      $rbx_msgs[] = "`$command_ns` commands list";
      $rbx_msgs[] = join(LF, $msgs);
    }

    call_user_func_array(array('rbx', 'box'), $rbx_msgs);
  }

/**
* show available commands
* @autocomplete command interactive_runner::get_commands_list
* @alias man
*/
  function help( $command = null){

    $rbx_msgs = array();

    if($this->classDoc) {
      $rbx_msgs [] = "Global usage";
      $rbx_msgs [] = join(LF, $this->classDoc['doc']);
    }

    $commands_list = $this->commands_list;
    if($command = $this->lookup($command)) 
      $commands_list = array_sort($commands_list, $command['command_hash']);


    foreach($commands_list as $command_hash=>$command_infos) {
      if($command_hash != $command['command_hash'] && ( $command_infos['usage']['hide']
        || $command_infos['command_ns'] == self::ns) ) continue;
        //title

      $msgs = $command_infos['usage']['doc'] ? $command_infos['usage']['doc'] : array();

      $rbx_msgs[] = "Command : {$command_infos['command_key']}";

      array_unshift($msgs, "Usage :". $this->help_cmd($command_infos));
      $rbx_msgs[] = join(LF, $msgs);
    }

    if(!$rbx_msgs) {
      rbx::error("No documentation available");
      return;
    }

    $rbx_msgs[] = array('notrim' => true);
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

  function register_alias($instance, $command_key, $alias_name, $args = array()){
    $this->command_aliases(get_class($instance), $command_key, array($alias_name=>$args) );
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
      'command_hash'=> $command_hash,
      'command_ns'  => $command_ns,
      'command_key' => $command_key,
      'usage'       => $usage,
      'callback'    => $callback,
      'aliases'     => array(),
    );

    $this->command_aliases($command_ns, $command_key, $command_key, $command_hash);
  }


  private $mandatory_arg_index; //autocomplete helper

  private function command_parse($command_prompt, $command_args = array(), $command_dict = array()) {

      if(!$command_prompt)
        throw new Exception("No command");

      $command_infos = $this->lookup($command_prompt);
      if($command_infos['usage']['cli'] && $this->command_pipe == self::LOOP_RUN) {
        if(cli::bool_prompt("Command only available through cli. Open help ?", false)) $this->help($command_infos['command_hash']);
        throw new Exception("Invalid command");
      }


      if(!$command_infos)
        throw rbx::error("Invalid command key '$command_prompt'");

      $alias_args        = $command_infos['aliases'][$command_prompt];

      if(is_array($alias_args))
          $command_args  = array_merge($alias_args, $command_args);

      $command_args_mask = $command_infos['usage']['params'];

      $this->mandatory_arg_index  = 0;
      $this->current_call['args'] = array();
      foreach($command_args_mask as $param_name=>$param_infos){

        if(array_key_exists($this->mandatory_arg_index, $command_args)){
          $param_in = $command_args[$this->mandatory_arg_index];
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
              $param_in = cli::text_prompt("\${$command_infos['command_ns']}[{$param_name}]", null);
              $this->current_command_completion = null;
            }
          }
        }

        $this->current_call['args'][$param_name] = $param_in;
        $this->mandatory_arg_index++;
      }
      cli::add_history($command_prompt.' '.join(' ', $this->current_call['args']));

      return array($command_infos['callback'], $this->current_call['args']);
  }

/**
* ends interactive session
* @alias q
* @alias exit
*/
  function quit(){
    $this->command_pipe = self::LOOP_QUIT;
    rbx::ok("Quit");
  }


/**
* @interactive_runner disable
* runner's internal looop, signal management
*/
  public function run() {
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
        if($res !== null) {
          if($this->output == self::OUTPUT_RAW)
            echo $res;
          elseif($this->output == self::OUTPUT_JSON)
            echo json_encode($res);
          else cli::box('Response', $res);
        }
      }

      if($run)
        exit();
    }

    $this->command_loop();
  }

  private $last_command = array();
  private static $REPLAY_COMMAND = array('r');

    //embeded object loop, deal with commands
  private function command_loop(){
   ///system("stty -icanon");
    $this->command_pipe = self::LOOP_RUN;

    while($this->command_pipe != self::LOOP_QUIT){

      try {
        $command_split = array();
        $this->mandatory_arg_index = null;
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
        if($res !== null) {
          if($this->output == self::OUTPUT_RAW)
            echo $res;
          elseif($this->output == self::OUTPUT_JSON)
            echo json_encode($res);
          else cli::box('Response', $res);
        }

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
* @api public
* @instance must be an object
* @instance can be a string describing a class name (for static classes)
* @interactive_runner hide
*/
  function reflection_scan($instance, $command_ns = null) {
    if(!$instance)
        throw new Exception("Invalid instance");

    $className = is_string($instance) ? $instance : get_class($instance);
    if(!$command_ns)
      $command_ns = $className;

    $reflect   = new ReflectionClass($className);
    $this->scan($reflect, $instance, $className, $command_ns);

    if($command_ns !== self::ns && ! isset($this->classDoc) )
      $this->classDoc = doc_parser::parse($reflect->getDocComment());
  }

  private function scan($reflect, $instance, $className, $command_ns) {

    $methods   = $reflect->getMethods();

    foreach($methods as $method) { 
      $method_name = $method->getName();
      $is_command = false;
      $callback   = null;
      $is_magic   = starts_with($method_name, "__");

      if($method->isPublic()
          && !$method->isStatic()
          && !$is_magic
          && !$method->isConstructor()
          && !is_string($instance)) {
        $callback = array($instance, $method_name);
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

      $tmp = isset($doc['args']['interactive_runner'])
             ? $doc['args']['interactive_runner']['computed'] : null;
      if(!$tmp) $tmp = array();
      if(in_array("disable", $tmp))
        continue;

      $usage = array('params'=>array(), 'doc' => $doc['doc'],
                'hide' => in_array("hide", $tmp),
                'cli'  => in_array("cli", $tmp), //only available via cli
      );

      foreach($params as $param) {
        $param_infos = array(
          'position'   => $param->getPosition(),
          'completion_values'   => null,
          'completion_callback' => null,
        );
        if($param->isOptional()){
          $param_infos['optional'] = true;
          $param_infos['default']  = $param->getDefaultValue();
        }
        $usage['params'][$param->getName()] = $param_infos;

      }
      $this->command_register($command_ns, $command_key, $callback, $usage);
      $command_hash = $this->generate_command_hash($command_ns, $command_key);

      if( isset($doc['args']['autocomplete']) ){
        foreach($doc['args']['autocomplete']['values'] as $values){
          $arg_name  = strip_start(array_shift($values), '$');
          $raw       = first($values);
          if(!isset($this->commands_list[$command_hash]['usage']['params'][$arg_name]))
            continue;

          unset($callback_definition);
          unset($statics_definition);
          $callback  = preg_match("#^([a-z0-9_]+)::([a-z0-9_]+)$#i", $raw, $callback_definition) && count($values) == 1;
          $statics   = preg_match("#^([a-z0-9_]+)::\\\$([a-z0-9_]+)$#i", $raw, $statics_definition) && count($values) == 1;

          $usage = &$this->commands_list[$command_hash]['usage']['params'][$arg_name];

          if($statics) {
            $class = new ReflectionClass($statics_definition[1]);
            $usage['completion_values'] = $class->getStaticPropertyValue($statics_definition[2]);
           } elseif($callback)
            $usage['completion_callback'] = array($callback_definition[1] == 'self' ? $className : $callback_definition[1], $callback_definition[2]);
          else
            $usage['completion_values'] = $values;

          unset($usage);
        }
     }

      if(isset($doc['args']['alias'])) foreach($doc['args']['alias']['values'] as $args) {
        $alias_name  = array_shift($args);
        if(!( $alias_name && $command_key)) continue;
        $this->command_aliases($command_ns, $command_key, array($alias_name=>$args) );
      }
    }

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

    $this->list_commands();
  }



  private static $current_runner = null;

/**
* @interactive_runner disable
*/
  static public function start($className, $args = array()) {

    if(!is_array($args))
      $args = array($args);

    $runner = new self();
      //register runners own commands
    if(is_object($className))
      $runner->reflection_scan($className);
     else if ($className == "SoapClient") {
      $source = new SoapClient($args[0]);
      $runner->reflection_scan_wsdl($source, "SoapClient");
    } else if(is_string($className)) {
      $runner->className = $className;
      $classes = array($className);
      if(array_get(cli::$dict, "ir://load"))
        $classes = array_merge($classes, (array) cli::$dict["ir://load"]);

      foreach($classes as $className) {
        if(!is_string($className))
          continue;
        ob_start(); //prevent autoloader to print anything (e.g. shebang)
        if(!class_exists($className)) //FORCE AUTOLOADER HERE !!
          throw new Exception("Invalid class '$className'");
        ob_end_clean();

        $reflector  = new ReflectionClass($className);

          // class { private function __construct(){} function instanciate(){} }
        $instanciate = $reflector->hasMethod('instanciate') && ( !$reflector->IsInstantiable()  || cli::$dict['ir://instanciate']);
        if($instanciate)
          $instance = call_user_func_array(array($className, 'instanciate'), $args);
        else
          $instance = $reflector->IsInstantiable()
                ? ($args && !is_null( $reflector->getConstructor ())
                   ? $reflector->newInstanceArgs($args)
                 : $reflector->newInstance() )
              : $className; //static

        $runner->scan($reflector, $instance, $className, $className);
      }
    }
  
    if(!empty(cli::$dict['ir://fs']))
      $runner->fullsize();
    else
      $runner->list_commands();

    self::$current_runner = $runner;
    $runner->run(); //private internal
  }

    //helpers for interactive runner completion
  private static function get_commands_list(){ return array_keys(self::$current_runner->commands_list); }

}
