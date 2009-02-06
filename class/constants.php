<?
/*	"Yks constants" by Leurent F. (131)
	distributed under the terms of GNU General Public License - © 2007 
*/


	//Usefull constants
define('LF',"\n");define('CRLF',"\r\n");
define('_NOW',$_SERVER['REQUEST_TIME']);
define('_UDAY',floor(_NOW/86400));


	//Engine detection


define('XSL_DOCUMENT',1);
define('XSL_NODE_SET',2);
define('XSL_ROBOT',3);
define('XSL_SERVER', XSL_NODE_SET);

include "engine_detection.php";


define('BOM', pack('C*',239,187,191));

	//Headers stds types
define('TYPE_XML',"Content-Type: text/xml; charset=utf-8");
define('TYPE_HTML',"Content-Type: text/html; charset=utf-8");
define('TYPE_XHTML',"Content-Type: application/xhtml+xml; charset=utf-8");
define('TYPE_TEXT',"Content-Type: text/plain; charset=utf-8");
define('TYPE_JSON',"Content-Type: text/json; charset=utf-8");
define('TYPE_CSS',"Content-Type: text/css;");
define('TYPE_PNG',"Content-Type: image/png;");
define('TYPE_JPEG',"Content-Type: image/jpeg;");
define('TYPE_ZIP',"Content-Type: application/zip;");
define('TYPE_JS',"Content-Type: application/x-javascript; charset=utf-8");
define('TYPE_FILE', "Content-type: application/octet-stream");
define('TYPE_PDF', "Content-type:application/pdf");
define('TYPE_CSV',"Content-type: application/vnd.ms-excel");
define('MIME_VERSION','MIME-Version: 1.0');
define('HTTP_CACHED_FILE',"Last-Modified: Thu, 12 Apr 2007 19:31:20 GMT");

	//XHTML stuffs
define('XML_VERSION','<?xml version="1.0" encoding="utf-8"?>'.LF);
define('XML_DOCTYPE','<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "">'.LF);
define('XML_EMPTY', '<![CDATA[]]>');

define('XHTML',"http://www.w3.org/1999/xhtml");


define('LIBXML_YKS',LIBXML_DTDLOAD|LIBXML_DTDATTR
		|LIBXML_NOENT
		|LIBXML_NOBLANKS|LIBXML_COMPACT
		|LIBXML_NOCDATA);
define('LIBXML_MYKS',LIBXML_YKS|LIBXML_DTDVALID);


define('YKS_INSTALLED',1);

define('MEMORY_USAGE',"ps -aeo rss | awk '{sum+=$1} END {print sum*1024}'");

$var_safe='\$[A-Z]?[a-z0-9_]*';
define("VAR_MASK","#$var_safe#e");	//usefull with preg_replace / safe mask
define("VAR_REPL",'$0');
define("FUNC_MASK","#[a-z0-9_:]+\(.*?\)#e");

define('MAIL_MASK','#([.0-9a-z_+-]+)@((?:[0-9a-z-]+\.)+[0-9a-z]{2,})#i');

	//Dates masks
define('DATE', '$d/$m/$Y');
define('DATE_DAY','&day_$N; $d &month_$n; $Y');
define('DATE_MONTH','&month_$n; $Y');
define('DATE_SAESON','&saeson_$a; $Y');
define('DATE_TXT', '$d &month_short_$n; $Y');


define('DATE_MASK','d/m/Y');	//input date format (validation)
define('FILE_MASK','#^[a-z_]+\.([a-z0-9_]{6,})\.([a-z_]{2,4})$#'); //upload file format
define('WHERE_MASK','`%2$s`=\'%1$s\'');    //Join masks
define('INI_MASK','%2$s=%1$s');    //Join masks


$action=(string)is_array($_POST['ks_action'])?key($_POST['ks_action']):$_POST['ks_action'];




define('XSL_ENGINE', $engine);



$charsets=array(
	'iso8859'=>array('latin1','latin1_general_ci'),
	'utf-8'=>array('utf8','utf8_general_ci')
);
$collations=array(
	'latin1_general_ci'=>'iso8859',
	'utf8_general_ci'=>'utf-8'
);

