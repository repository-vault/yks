<?
define('KRA_URL',"http://www.kraland.org");
define('KRA_XML_CACHE_PATH', "$cache_path/kraland/xml");
define('KRA_JS_CACHE_PATH', "$cache_path/kraland/js");



data::register("kraland_cities", "get_kra_city", "$class_path/apis/kraland/cybermonde.php");
data::register("kraland_states", "get_kra_city", "$class_path/apis/kraland/cybermonde.php");

