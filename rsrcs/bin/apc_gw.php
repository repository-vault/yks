<?php

if(PHP_SAPI == "cli")
  die("apc gw only works from apache2 handler");


$related = $_SERVER['SCRIPT_NAME'] . "/";
$start   = $_SERVER['REQUEST_URI'];

if(substr($start,0,strlen($related)  ) != $related )
  die("Bad configuration");

$apc_key = substr($start, strlen($related) );


$method = strtolower($_SERVER['REQUEST_METHOD']);

switch($method){
  case "get":
    $output = $apc_key ? apc_fetch($apc_key) : "pong";
    break;
  case "post":
  case "put":
  case "update":
    $contents = stream_get_contents(fopen("php://input", "r"));
    $contents = unserialize($contents);
    $output = apc_store($apc_key, $contents);
    break;
  case "delete":
    $output = apc_delete($apc_key);
    break;
  default:
    die("Invalid operation $method");
}

die(serialize($output));
