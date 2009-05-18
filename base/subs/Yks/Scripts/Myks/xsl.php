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


    foreach($browsers_engine as $engine_name => $engine_infos){
        $client = $xsl_cache->out( $engine_infos['mykse_url'], $engine_infos['external_mode'],
            $engine_name, "client" );

        rbx::ok("$engine_name (client) reloaded into $client");

        $server = $xsl_cache->out( $server_side['mykse_url'], $server_side['external_mode'],
            $engine_name, "server");

        rbx::ok("$engine_name (server) reloaded into $server");

    }

    $out_file = xsl_cache::out_file("robot", "server");
    copy(RSRCS_PATH."/xsl/specials/validator.xsl", $out_file); 
    rbx::ok("robot (server) reloaded into $out_file");

     rbx::line();
