<?

if(!defined('Ex/yks')) die("Ex/yks is not loaded");

  ob_start( );
  exyks::bench('generation_start');

  list($context, $href, $href_ks, $depths, $argv0) = exyks::context_prepare($_SERVER['QUERY_STRING']);
    try { foreach($context as $depth=>$infos){
        list($subs_path, $subs_fold, $page, $args, $href_fold, $href_base) = $infos;
        if($depth==$depths) exyks::$href = $href = "$href_base/$page";
        list($sub0, $sub1, $sub2, $sub3, $sub4) = $args;
        $subs_file = "$subs_fold/$page"; exyks::$page_def = "home";
        if(!is_file($file = "$subs_path/$page.php"))abort(404);
        include $file;
    }} catch(Exception $e){ rbx::error("Aborting all operations.", 1, true); }
  exyks::context_end();


  exyks::render_prepare( compact('href_base', 'href_fold', 'subs_file', 'href') );

    foreach(tpls::$top as $top) include $top;
    include tpls::$body;
    foreach(tpls::$bottom as $bottom) include $bottom;

  exyks::render();
