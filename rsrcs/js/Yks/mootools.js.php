<?
header(TYPE_JS);
header(HTTP_CACHED_FILE);

include "$class_path/dsp/js/loader.php";


include "exts/mootools.php";
include "exts/mootools-extended.php";


$js_file = build_js_cache($js_build_list, $js_prefixs);

readfile($js_file);
die;