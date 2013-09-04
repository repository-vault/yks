<?

if(PHP_SAPI == "cli")
  die("apc gw only works from apache2 handler");


$related = $_SERVER['SCRIPT_NAME'] . "/";
$start   = $_SERVER['REQUEST_URI'];

if(substr($start,0,strlen($related)  ) != $related )
  die("Bad configuration");

$apc_key = substr($start, strlen($related) );
if(!$apc_key)
  die("Invalid APC key");


$method = strtolower($_SERVER['REQUEST_METHOD']);
switch($method){
  case "get":
    die(apc_fetch($apc_key));
    break;
  case "post":
  case "put":
  case "update":
    $contents = stream_get_contents(fopen("php://input", "r"));
    apc_store($apc_key, $contents);
    die("wrote ".strlen($contents));
  case "delete":
    apc_delete($apc_key);
    die("entry deleted");
  default:
    die("Invalid operation $method");
}

