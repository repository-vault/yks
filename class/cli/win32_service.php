<?

abstract class win32_service {
  var $run_last_time;
  var $run_time_interval = 5;
  var $run_loop_sleep = 2;
  var $service_key;
  var $service_file;
  var $service_display;

  abstract function run();

  final function __construct($action){

    $this->__check_extension();

    if(!$this->service_key)
        $this->service_key = get_class($this);

    if(!$this->service_file)
        $this->service_file = dirname($this->__find_file());

    chdir($this->service_file);
    if(!$this->service_display)
        $this->service_display = "PHP service : {$this}";

    $actions = array('start', 'install', 'uninstall', 'stop', 'status', 'restart');
    if(in_array($action, $actions))
        return $this->$action();
    if($this->is_service_mode())
        return $this->loop($action);

    $this->abort("Unknow action : '$action' ".', available commands are ['.join('|',$actions).']');
  }

  function init(){}


  function is_service_mode(){
    return PHP_SAPI == 'cli' && !isset($_ENV['HOMEPATH']);
  }

  function ready(){
    return (time() - $this->run_last_time) > $this->run_time_interval;
  }

  function reset(){
    $this->run_last_time = time();
  }

  function log($msg){
    echo $msg.CRLF;
  }
  function abort($msg){
    $this->log($msg);
    die;
  }
  function __toString(){
    return $this->service_key;
  }


  private final function install(){
    $params = array(
        'service' => $this->service_key,
        'display' => $this->service_display,
        'params' => $this->service_file,
    );

    $createService = win32_create_service($params);
    if($createService !== true)
        $this->log("Unable to create service {$this}");
    else $this->log("Service {$this} has been sucessfully registred");
  }

  private final function uninstall(){
    $removeService = win32_delete_service($this->service_key);
    switch($removeService) {
        case 1060: $this->log("Unknow service {$this}");break;
        case 1072: $this->log("Unable to unregister service {$this}, it is probably running or corrupted");break;
        case 0:$this->log("Service {$this} has been sucessfully unregistred");break;
        default:$this->log("Unknow error $removeService");
    }
  }

  private final function start($break = true){
    
    if(win32_start_service($this->service_key) !== WIN32_NO_ERROR)
        $this->log("Unable to start service {$this}");
    else $this->log("Service {$this} started");
  }



  private final function restart(){
    $this->stop(true);
        sleep($this->run_loop_sleep*2);
    $this->start(true);
 }

  private final function stop($quiet = false){
    if(win32_stop_service($this->service_key) !== WIN32_NO_ERROR)
        $this->log("Unable to stop service {$this}");
    else $this->log("Service {$this} stopped");
  }

  private final function status(){
    $status_infos = win32_query_service_status($this->service_key);
    $currentState = $status_infos['CurrentState'];
    $status_list = array(
        'WIN32_SERVICE_STOPPED'=>"Service stopped",
        'WIN32_SERVICE_RUNNING'=> "Service running",
        'WIN32_SERVICE_STOP_PENDING'=>"Service stopping"
    );
    $status=0;
    foreach($status_list as $status_name=>$status_descr){
        $value = constant($status_name);
        if(!(($currentState & $value) == $value && $value > $status))
            continue;
        $status = $value;
        $status_str = $status_descr;
    } if(!$status) $status_str = "Unknow status";
    $this->log("Service {$this} status : $status_str (#$currentState)");
  }

  private final function loop(){
    $this->init();

    $x = win32_start_service_ctrl_dispatcher($this->service_key);
    if($x !== true) 
        $this->abort("Dispatcher error on service {$this} (#$x)");

    while (WIN32_SERVICE_CONTROL_STOP != win32_get_last_control_message()) {

        if($this->ready()){
            $this->reset();
            $this->run();
        }

        sleep($this->run_loop_sleep);
    }
  }

  private final function __find_file(){
    $reflect = new ReflectionObject($this);
    return $reflect->getFileName();
  }

  private final function __check_extension(){
    $so = 'php_win32service';
    if(!extension_loaded($so))
        dl("$so.dll") or $this->abort("Unable to load extension $so");
    return true;
  }


}
