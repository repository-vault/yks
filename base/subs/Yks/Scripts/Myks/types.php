<?

    rbx::title("Parsing myks definitions");

  //parse&update mykse XML

    $myks_gen = new myks_parser($myks_config);

    $types_xml      = simplexml_import_dom($myks_gen->out("mykse"));
    $tables_xml     = simplexml_import_dom($myks_gen->out("table"),"field");
    $procedures_xml = simplexml_import_dom($myks_gen->out("procedure"));
    $views_xml      = simplexml_import_dom($myks_gen->out("view"));


    $types_xml->asXML($mykse_filename);	// export
    rbx::ok("Myks types $mykse_filename updated");

        //only for APC update (!!$tables_xml is not simplexml, but field )
    $types_xml=data::reload("types_xml"); 
    rbx::ok("APC types_xml cache reloaded");

    $tables_xml_test=data::reload("tables_xml");
    rbx::ok("APC tables_xml cache reloaded");

    //echo chunk_split($tables_xml_test->asXML(),90);die('here');

    if(!$types_xml instanceof SimpleXMLElement) die("Unable to load types_xml");


    rbx::box("Scanning : mykses paths", $myks_gen->myks_paths);

        //before export ? after ? i don't know is it's very usefull
    if(!$types_xml->myks_type) {
        rbx::warn("Unable to locate type : 'myks_type', skipping");
    }

    rbx::line();


