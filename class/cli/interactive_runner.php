<?

class interactive_runner {
  private $obj;
  private $className;
  private $commands_list;

  private $command_pipe;



  private function __construct($obj){
    $this->obj = $obj;
    $this->className = get_class($this->obj);

    $this->reflection_scan($this->className, $this->obj);
    rbx::ok("Runner is ready '{$this->className}'");

    $this->reflection_scan("runner", $this); //register runners own commands

    $this->command_aliases("runner", "help", array("?") );
    $this->command_aliases("runner", "quit", array("q") );

    $this->help();

  }


  function help(){
    $msgs = array();

    foreach($this->commands_list as $command_hash=>$command) {

      $str = "{$command['command_key']} ";
      $aliases  = array_diff($command['aliases'], array($command['command_key'], $command_hash));
      if($aliases) $str.= "(".join(', ', $aliases).") ";

      if($command['usage']['params'])
        $str .= join(' ', array_keys($command['usage']['params']));
      $msgs[$command['command_ns']][] = $str;
    }

    $rbx_msgs = array();
    foreach($msgs as $command_ns=>$msgs) {
        //title
      $rbx_msgs[] = $command_ns == $this->className ? "Commands list" : "From $command_ns";
      $rbx_msgs[] = join(LF, $msgs);
    }

    call_user_func_array(array('rbx', 'box'), $rbx_msgs);
  }

  private function command_aliases($command_ns, $command_key, $aliases){
    $command_hash = "$command_ns::$command_key";
    if(!isset($this->commands_list[$command_hash]))
      return false;
    $this->commands_list[$command_hash]['aliases'] = array_merge(
        $this->commands_list[$command_hash]['aliases'],
        !is_array($aliases) ? array($aliases) : $aliases
    );
    return true;
  }

  private function command_register($command_ns, $command_key, $callback, $usage){
    $command_hash = "$command_ns::$command_key";
    $this->commands_list[$command_hash] = array(
      'command_ns'  => $command_ns,
      'command_key' => $command_key,
      'usage'       => $usage,
      'callback'    => $callback,
      'aliases'     => array($command_key, $command_hash)
    );
  }

  function php($cmd){
    $args = func_get_args(); $args = join(' ', $args);
    rbx::ok("Exec $args");
    eval($args);
  }
 
  private function command_parse($command_str) {
      $command_str = trim($command_str);

      $command = preg_split("#\s+#", $command_str, 2);
      list($command_key, $args) = $command;
      $command_resolve = array();
      foreach($this->commands_list as $command_hash=>$command_infos)
        if(in_array($command_key, $command_infos['aliases']))
          $command_resolve[] = $command_hash;

      if(!$command_str)
        throw new Exception("No command");

      if(!$command_resolve)
        throw rbx::error("Invalid command key '$command_key'");

      if(count($command_resolve) > 1)
        throw rbx::error("Too many results for command '$command_key', please specify ns");

      $command_hash     = $command_resolve[0];
      $command_callback = $this->commands_list[$command_hash]['callback'];
      $command_args = preg_split("#\s+#", $args); //!
      return array($command_callback, $command_args);
  }

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
    //embeded object loop, deal with commands
  private function command_loop(){

    while(is_null($this->command_pipe)){ 

      try {
        $command_str = cli::text_prompt('$'.$this->className);
        list($command_callback, $command_args) = $this->command_parse($command_str);
        
      } catch(Exception $e){ continue; }


      try {
        call_user_func_array($command_callback, $command_args);
      } catch(Exception $e){
        echo CRLF;
        rbx::box("!! Uncatched exception !!", $e->getMessage(), "trace", $e->getTraceAsString());
        continue;
      }

    }

  }


  private function reflection_scan($command_ns, $object){
    $reflect = new ReflectionObject($object);
    $methods = $reflect->getMethods();

    foreach($methods as $method) {
      $is_command = $method->isPublic()
                    && !$method->isStatic()
                    && !$method->isConstructor();
      if(!$is_command)
        continue;

      $usage = array();
      $params = $method->getParameters();

      $doc = doc_parser::parse($method->getDocComment());

      foreach($params as $param)
        $usage['params'][$param->getName()] = array();

      $command_key = $method_name = $method->getName();

      $this->command_register($command_ns, $command_key, array($object, $method_name), $usage);

      if($doc['args']['alias']) $this->command_aliases($command_ns, $command_key,  $doc['args']['alias']['computed']);
    }

  }

  public static function start($obj){

    $runner = new self($obj);
    $runner->main_loop();
  }
}

