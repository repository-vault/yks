<?php
/*  "Yks loader" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2009
*/


define('Ex/yks', 'A cloudy tool');

  //CLI need to know the server port (80 by default)
  if(!isset($_SERVER['SERVER_PORT'])) $_SERVER['SERVER_PORT'] = 80;
      //first thing first, where am i
  if(!defined('PUBLIC_PATH')) {
      //where am i
    $public_root = dirname($_SERVER['SCRIPT_FILENAME']);
    if(PHP_SAPI == 'cli') {
        if(!$_SERVER['SERVER_NAME']) $_SERVER['SERVER_NAME'] = 'cli';
        define('PUBLIC_PATH', realpath($public_root));
        define('ROOT_PATH',   PUBLIC_PATH);
    } else {
            //remove relatives paths
        if($rel = ltrim(dirname($_SERVER['SCRIPT_NAME']),'\/'))
          $public_root = substr($public_root, 0, -strlen($rel)-1);
        define('PUBLIC_PATH', realpath($public_root));
    }
  }


  $class_path  = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..');


  define('WWW_PATH',    PUBLIC_PATH);
  define('ROOT_PATH',   dirname(WWW_PATH));
  define('CONFIG_PATH', realpath(ROOT_PATH.DIRECTORY_SEPARATOR."config")); 
  define('CLASS_PATH',  $class_path);
  define('YKS_PATH',    realpath("$class_path/.."));

  define('LIBS_PATH',    YKS_PATH.DIRECTORY_SEPARATOR.'libs');
  define('RSRCS_PATH',   YKS_PATH.DIRECTORY_SEPARATOR.'rsrcs');
  define('CLTOOLS_PATH', YKS_PATH.DIRECTORY_SEPARATOR.'cltools');

  $win = stripos($_SERVER['OS'],'windows')!==false ;
  define('CLYKS',  $win ? "clyks.bat" : "clyks");
  define('EXYKS',        YKS_PATH.DIRECTORY_SEPARATOR.'web.php');
  define('SERVER_NAME',  strtolower($_SERVER['SERVER_NAME']));

include "$class_path/constants.php";
include "$class_path/stds/classes.php";
include "$class_path/stds/zero_functions.php";
include "$class_path/yks/yks.php";

if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
    classes::extend_include_path("$class_path/psr4");
    include "$class_path/stds/pcntl.php";
}

$__YKS_FREE = isset($_SERVER['YKS_FREE'])?$_SERVER['YKS_FREE']:false;
$load_config = PHP_SAPI != 'cli' && !$__YKS_FREE || defined('yks/cli');

yks::init($load_config);

