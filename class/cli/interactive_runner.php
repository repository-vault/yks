<?

class interactive_runner {
  private $obj;
  private $className;
  private $commands_list;

  private $command_pipe;

  static function init(){
    if(!classes::init_need(__CLASS__)) return;

    classes::register_class_path("doc_parser", CLASS_PATH."/apis/doc/parse.php");
  }

  private function __construct($obj){
    $this->obj = $obj;
    $this->className = get_class($this->obj);

    $this->reflection_scan($this->className, $this->obj);
    rbx::ok("Runner is ready '{$this->className}'");

    $this->reflection_scan("runner", $this); //register runners own commands

    $this->command_aliases("runner", "help", "?");
    $this->command_aliases("runner", "quit", "q");

    $this->help();

  }


  function help(){
    $msgs = array();

    foreach($this->commands_list as $command_hash=>$command) {

      $str = "{$command['command_key']} ";
      $parametred_aliases = array_filter($command['aliases']);

      $aliases  = array_keys(array_diff_key($command['aliases'], $parametred_aliases));
      $aliases  = array_diff($aliases, array($command['command_key'], $command_hash));
      if($aliases) $str.= "(".join(', ', $aliases).") ";

      if($command['usage']['params'])
        $str .= join(' ', array_keys($command['usage']['params']));
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

      if(!$command_resolve)
        throw rbx::error("Invalid command key '$command_prompt'");

      if(count($command_resolve) > 1)
        throw rbx::error("Too many results for command '$command_prompt', please specify ns");

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
        $command_split = array();
        cli::text_prompt('$'.$this->className, $command_split);
        list($command_callback, $command_args) = $this->command_parse($command_split);
        
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
    $reflect   = new ReflectionObject($object);

    $methods   = $reflect->getMethods();
    $className = $reflect->getName();

    foreach($methods as $method) { $method_name = $method->getName();

      $is_command = false;
      $callback   = null;
      if($method->isPublic()
          && !$method->isStatic()
          && !$method->isConstructor()) {
        $callback = array($object, $method_name);
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

      foreach($params as $param)
        $usage['params'][$param->getName()] = array(); //!

      $this->command_register($command_ns, $command_key, $callback, $usage);

      if($doc['args']['alias']) {
        $aliases = array_fill_keys($doc['args']['alias']['computed'], false);
        $this->command_aliases($command_ns, $command_key, $aliases);
      }

    }

      //aliases with args
    $doc = doc_parser::parse($reflect->getDocComment());
    $aliases = $doc['args']['command_alias'];
    if($aliases) foreach($aliases['values'] as $args) {
        $alias_name  = array_shift($args);
        $command_key = array_shift($args);
        if(!( $alias_name && $command_key)) continue;
        $this->command_aliases($command_ns, $command_key, array($alias_name=>$args) );
    }
    
  }

  public static function start($obj){

    $runner = new self($obj);
    $runner->main_loop();
  }
}

