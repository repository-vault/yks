<?

$js_config = config::retrieve("js");


    //parse js_namespaces from configuration file
$js_namespaces = array();
foreach($js_config->js_namespaces->ns as $ns){
    $path = preg_replace(CONST_MASK, CONST_REPL, $ns['path']);
    $fpi = (string) $ns['fpi'];
    $js_namespaces[$fpi] = $path;
}
$js_headers_path = paths_merge(ROOT_PATH, $js_config["headers_path"], "config/js");

if(!is_dir($js_headers_path))
    throw rbx::error("Invalid js headers path");

include "$class_path/dsp/js/builder.php";

$headers_files = files::find($js_headers_path, '.*.xml$');

js_builder::init($js_namespaces);
js_builder::parse_headers($headers_files);
die;

print_r($headers_files);
print_r($js_namespaces);
print_r($js_config);
