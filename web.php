<?

if(!defined('Ex/yks')) die("Ex/yks is not loaded");
  $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);

  ob_start( );
  exyks::bench('generation_start');

  $vars = array();
  list($context, $href, $href_ks, $depths, $argv0) = exyks::context_prepare($_SERVER['QUERY_STRING']);
    foreach($context as $depth=>$infos){
        list($subs_path, $subs_fold, $page, $subs_args, $href_fold, $href_base) = $infos;
        if($depth==$depths) exyks::$href = $href = "$href_base/$page";
        list($sub0, $sub1, $sub2, $sub3, $sub4) = $subs_args;
        $subs_file = "$subs_fold/$page"; exyks::$page_def = "home";
        if(!is_file($file = "$subs_path/$page.php"))abort(404);
        exyks::$vars['tmp'] = array_keys($GLOBALS);
        include $file;
        exyks::$vars[$file.join(',', $subs_args)] = array_diff(array_keys($GLOBALS), exyks::$vars['tmp']);
    }
  exyks::context_end();


  exyks::render_prepare( compact('href_base', 'href_fold', 'subs_file', 'href', 'href_ks') );

  $tpls_list = tpls::export_list(exyks::retrieve("RENDER_MODE"));
  foreach($tpls_list as $file)
    include $file;

  $str = ob_get_contents(); ob_end_clean();
  exyks::render($str);
