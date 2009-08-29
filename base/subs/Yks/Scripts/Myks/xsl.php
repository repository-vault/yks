<?

rbx::title("Updating XSL cache files");

files::delete_dir(XSL_CACHE_PATH,false);
files::create_dir(XSL_CACHE_PATH);

  //xsl update


    $doc = new DOMDocument("1.0");
    $xsl = new XSLTProcessor();

    $doc->load($xsl_filename,LIBXML_NOENT);
    $xsl->importStyleSheet($doc);

    $doc->load($xml_filename);

    $xsl_cache = new xsl_cache($doc, $xsl);
    $xsl_cache->parameters_add(compact('mykse_file_path', 'mykse_file_url'));


    foreach($browsers_engine as $engine_name)
      foreach($rendering_sides as $rendering_side) {
        $file = $xsl_cache->out($engine_name, $rendering_side); 
        rbx::ok("$engine_name ($rendering_side) reloaded into $file");
      }

    $out_file = xsl_cache::out_file("robot", "server");
    copy(RSRCS_PATH."/xsl/specials/validator.xsl", $out_file); 
    rbx::ok("robot (server) reloaded into $out_file");

     rbx::line();
