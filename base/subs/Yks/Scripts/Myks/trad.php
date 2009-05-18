<?

    rbx::title("Starting localization");

    $result = locales_fetcher::fetch_all();
    if(!$result)
        rbx::error("Please define at least one language");
    else foreach($result as $infos)
        rbx::ok("Entities {$infos[0]} reloaded ({$infos[1]})");

    rbx::line();
