<?
/*  "Ex/yks loader" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2009
*/


define('Ex/yks', 'Exyks, Exupery style');

      // from this file path
  $class_path=realpath(dirname(__FILE__).'/..');
  define('YKS_PATH', realpath("$class_path/.."));

  $public_root = dirname($_SERVER['SCRIPT_FILENAME']);
    //remove www or relatives paths
  if($rel=ltrim(dirname($_SERVER['SCRIPT_NAME']),'\/'))
    $public_root = substr($public_root, 0, -strlen($rel)-1);
  define('PUBLIC_PATH', realpath($public_root));

    //Ex/yks specifics
  define('EXYKS_PATH', dirname(realpath($_SERVER['SCRIPT_FILENAME'])));
  define('WWW_PATH', PUBLIC_PATH);
  define('CONFIG_PATH', dirname(WWW_PATH)."/config"); //one level up than www root


include "$class_path/constants.php";
include "$class_path/config.php";
include "$class_path/stds/classes.php";
include "$class_path/zero_functions.php";
include "$class_path/yks/yks.php";
include "$class_path/exyks/exyks.php";


    //someday, this should end up in exyks::initialize()...

$screen_id=$_SERVER['HTTP_SCREEN_ID'];

$tmp = (string)$config->site['default_mode'];
define('DEFAULT_MODE',$tmp?$tmp:"xml");
$default=ROBOT||IPOD?"html":DEFAULT_MODE;

if($screen_id || $_POST['jsx'])$mode="jsx";
else $mode=$default;
$screen_id = 10;

define('JSX', $mode=="jsx");


exyks::store('RENDER_MODE', JSX?"jsx":"full");
exyks::store('RENDER_SIDE', ($mode=="html"?"server":"client")); //rbx is a render_side too


exyks::store('HEADERS', array(
    'full-server'=>ROBOT?TYPE_XHTML:TYPE_HTML,
    'full-client'=>TYPE_XML,
    'jsx-client'=>TYPE_XML,
));

exyks::store('LANGUAGES', preg_split("#[,\s]+#", $config->languages['keys']));
define('JSX_TARGET', $_SERVER['HTTP_CONTENT_TARGET']);


$site_code = SITE_CODE;
$site_url  = SITE_URL;
$site_base = SITE_BASE;

$verif_site = compact("site_code");
$site_name  = "&site.$site_code;";

$site_xsl =                     CACHE_URL."/xsl/{$engine}_client.xsl"; // relative
exyks::store('XSL_PATH',        "$site_url/$site_xsl");
exyks::store('XSL_SERVER_PATH', CACHE_PATH."/xsl/{$engine}_server.xsl");

