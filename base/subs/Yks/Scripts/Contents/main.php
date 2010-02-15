<?

$hash = $sub0;
$path = $argv0;

//expose only themes' files (css/png)

$full = exyks_paths::resolve($path);

$check = crpt("$path/$full", FLAG_FILE);
if($check != $hash) die("Invalid hash");

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


die;