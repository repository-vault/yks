<?
/*  "Yks loader" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2009
*/


define('Yks', 'A cloudy tool');

      // from this file path
  $class_path=realpath(dirname(__FILE__).'/..');
  define('CLASS_PATH', $class_path);
  define('YKS_PATH', realpath("$class_path/.."));

  $public_root = dirname($_SERVER['SCRIPT_FILENAME']);
    //remove www or relatives paths
  if($rel=ltrim(dirname($_SERVER['SCRIPT_NAME']),'\/'))
    $public_root = substr($public_root, 0, -strlen($rel)-1);
  define('PUBLIC_PATH', realpath($public_root));

    //Yks specifics
  define('ROOT_PATH', PUBLIC_PATH); //ROOT_PATH wont be overriden
  define('CONFIG_PATH', realpath(ROOT_PATH."/config")); 


include "$class_path/constants.php";
include "$class_path/config.php";
include "$class_path/stds/classes.php";
include "$class_path/zero_functions.php";
include "$class_path/yks/yks.php";

