<?
define('KRA_URL',"http://www.kraland.org");
define('KRA_XML_CACHE_DIR', "$cache_dir/kraland/xml");
define('KRA_JS_CACHE_DIR', "$cache_dir/kraland/js");



data::register("kraland_cities", "get_kra_city", "$class_path/apis/kraland/cybermonde.php");
data::register("kraland_states", "get_kra_city", "$class_path/apis/kraland/cybermonde.php");

