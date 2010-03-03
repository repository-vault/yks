<?


die("TODO");
$headers_files = files::find($js_headers_path, '.*.xml$');

js_builder::init();
js_builder::parse_headers($headers_files);
die;

print_r($headers_files);
print_r($js_namespaces);
print_r($js_config);
