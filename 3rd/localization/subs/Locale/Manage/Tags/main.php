<?

tpls::page_def("list");


$tag_id = (int)$sub0;

if($tag_id) try {
    $locale_tag = new locale_tag($tag_id);
} catch(Exception $e){
    abort(101); //invalid tag id #
} else $locale_tag = null;
