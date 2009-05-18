<?

$part_id = (int) $sub0;
$mode_collapsed = (bool) $sub1;

$verif_parent = array('parent_part'=>$part_id );
$verif_part = compact('part_id');


tpls::page_def("types/collapsed");



if(!$part_id )
    return; //there should be an error here

$part_infos = sql::row("ks_mails_parts", $verif_part );

if($mode_collapsed) return;

list($part_type_primary, $part_type_secondary) = explode("/", $part_infos['content-type']);

if($part_type_primary == "multipart") {
    sql::select("ks_mails_parts",$verif_parent,'`part_id`, `content-type`');
    $part_children = sql::brute_fetch('part_id');
}

if($action == "part_delete") try {
    sql::delete("ks_mails_parts", $verif_part);
    return jsx::js_eval("this.getBox().opener.reload()");

}catch(rbx $e){}

tpls::page_def("types/$part_type_primary-$part_type_secondary");

