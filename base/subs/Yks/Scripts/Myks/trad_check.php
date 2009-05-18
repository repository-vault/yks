<?

    if(!$types_xml) $types_xml = yks::$get->types_xml;

    $languages = exyks::retrieve('LANGUAGES');
    if(!$languages) return rbx::error("Please define at least one language");


    $entities=array();
    foreach($languages as $lang_key){
        $entities[$lang_key]=data::reload("entities",$lang_key);
        rbx::ok("Entity $lang_key reloaded");
    }

    rbx::ok("Diff en->fr");


print_r(array_keys(array_diff_key($entities['en-us'],$entities['fr-fr'])));

    rbx::ok("Diff fr->en");

print_r(array_keys(array_diff_key($entities['fr-fr'],$entities['en-us'])));


die;
