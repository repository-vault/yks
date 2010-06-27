<?

$hash = $sub0;
$path = $argv0;

//expose only themes' files (css/png)


$path_infos = parse_url($path);
$domain = $path_infos['host'];


$exposed_domains = array('public', 'skin', 'cache');
if(!in_array($domain, $exposed_domains))
    die("Unaccessible path $path");


$full = exyks_paths::resolve($path);



$ext = files::ext($full);

if($ext == "css") {
    header(TYPE_CSS);
    files::highlander();
    //css_processor might be able to compress ?
    $process = new css_processor($path);
    echo $process->output();
    die;
}

if($ext == "png") {
    header(TYPE_PNG);
    files::delivers($full);
}

if(in_array($ext, array("jpeg", "jpg"))) {
    header(TYPE_JPEG);
    files::delivers($full);
}


die;