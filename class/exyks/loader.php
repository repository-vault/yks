<?
/*	"Yks loader" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2007 
*/


define('Ex/yks', 'Exyks, Exupery style');

$class_path=realpath(dirname(__FILE__).'/..');


define('YKS_PATH', realpath("$class_path/.."));
define('EXYKS_PATH', dirname(realpath($_SERVER['SCRIPT_FILENAME'])));

$root_path=dirname($_SERVER['SCRIPT_FILENAME']);

if($rel=ltrim(dirname($_SERVER['SCRIPT_NAME']),'\/'))
    $root_path=substr($root_path,0,-strlen($rel)-1);
$root_path = realpath($root_path);

define('ROOT_PATH', $root_path);
define('CONFIG_PATH', ROOT_PATH."/config");
define('TMP_PATH', CONFIG_PATH."/tmp");

include "$class_path/constants.php";
include "$class_path/config.php";
include "$class_path/stds/classes.php";
include "$class_path/zero_functions.php";

include "$class_path/yks.php";
include "$class_path/exyks/exyks.php";


$tmp = (string)$config->site['default_mode'];
define('DEFAULT_MODE',$tmp?$tmp:"xml");

$default=ROBOT||IPOD?"html":DEFAULT_MODE;

$screen_id=$_SERVER['HTTP_SCREEN_ID'];

preg_match("#^([a-z]+)/([a-z]+)#",$_SERVER['HTTP_ACCEPT'],$accept);
list($accept, $accept_main, $accept_spec) = $accept;

if($screen_id || $_POST['jsx'])$mode="jsx";
else $mode=$default;
$screen_id = 10;

define('MODE', exyks::store('MODE', $mode));

exyks::store('HEADERS_MODE', array(
    'xml'=>TYPE_XML,
    'html'=>ROBOT?TYPE_XHTML:TYPE_HTML,
    'pop'=>TYPE_XML,
    'jsx'=>TYPE_XML,
    'src'=>TYPE_TEXT,
    'img'=>TYPE_PNG,
    'inframe'=>TYPE_XML,
));


define('JSX', MODE=="jsx");
define('JSX_TARGET', $_SERVER['HTTP_CONTENT_TARGET']);


$site_code=SITE_CODE;
$site_url=SITE_URL;
$site_base=SITE_BASE;

$verif_site=compact("site_code");
$site_name="&site.$site_code;";

$cache_dir      = $root_path.'/'.CACHE_DIR;
$js_cache_dir   = "$cache_dir/js";
$xsl_cache_dir	= "$cache_dir/xsl";  //see site_xsl
$xml_cache_dir	= "$cache_dir/xml";
$img_cache_dir	= "$cache_dir/imgs";

$site_xsl= CACHE_DIR."/xsl/{$engine}_client.xsl"; // relative
define('XSL_PATH',"$site_url/$site_xsl");
define('XSL_SERVER_PATH', "$xsl_cache_dir/{$engine}_server.xsl");


