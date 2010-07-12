<?php
/*  "Yks loader" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2009
*/


define('Ex/yks', 'A cloudy tool');



    //where am i
  $public_root = dirname($_SERVER['SCRIPT_FILENAME']);

      //first thing first, where am i
  if(!defined('PUBLIC_PATH')) {
    if(PHP_SAPI == 'cli') {
        define('PUBLIC_PATH', realpath($public_root));
        define('ROOT_PATH',   PUBLIC_PATH);
    } else {
            //remove relatives paths
        if($rel = ltrim(dirname($_SERVER['SCRIPT_NAME']),'\/'))
          $public_root = substr($public_root, 0, -strlen($rel)-1);
        define('PUBLIC_PATH', realpath($public_root));
    }
  }


  $class_path  = realpath(dirname(__FILE__).'/..');


  define('WWW_PATH',    PUBLIC_PATH);
  define('ROOT_PATH',   dirname(WWW_PATH));
  define('CONFIG_PATH', realpath(ROOT_PATH."/config")); 
  define('CLASS_PATH',  $class_path);
  define('YKS_PATH',    realpath("$class_path/.."));

  define('LIBS_PATH',    YKS_PATH.'/libs');
  define('RSRCS_PATH',   YKS_PATH.'/rsrcs');
  define('CLTOOLS_PATH', YKS_PATH.'/cltools');

  define('EXYKS',        YKS_PATH.'/web.php');
  define('SERVER_NAME',  strtolower($_SERVER['SERVER_NAME']));

include "$class_path/constants.php";
include "$class_path/stds/classes.php";
include "$class_path/zero_functions.php";
include "$class_path/yks/yks.php";


$load_config = PHP_SAPI != 'cli' && !$_SERVER['YKS_FREE'] || defined('yks/cli');

yks::init($load_config);




