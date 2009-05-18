<?


try {
    $cache_path = CACHE_PATH; //for rbx purposes only
    files::create_dir(CACHE_PATH);
    if(!is_writable(CACHE_PATH))
        throw rbx::error("$cache_path is not writable");

    files::create_dir(XML_CACHE_PATH);

    $me = trim(`id -un`).':'.trim(`id -gn`); $me_id = trim(`id -u`);

    $cache_owner = fileowner(CACHE_PATH);
    if($cache_owner!=$me_id)
        rbx::error("Please make sure cache directory :'$cache_path' is owned by $me");

} catch(Exception $e){ rbx::error("Unable to access cache directory '$cache_path'"); die; }
