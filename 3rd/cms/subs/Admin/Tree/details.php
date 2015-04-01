<?php


$revision_date = (int) $sub0;
$preview       = $sub0=="preview";


tpls::export(compact('node_id'));
if($action == "node_save") try {

    $node->save($_POST);
    rbx::ok("Node successfully saved");

    if($revision_date)
        jsx::js_eval("Jsx.open('/?$href', false, this)");

} catch( rbx $e){}

if($revision_date) try {
    $node = $node->restore_state($revision_date);

}catch(Exception $e){jsx::$rbx = true; rbx::error("CHANGE");}



$versions = array();

foreach($node->states as $version)
    $versions[$version] = date('d/m/Y H:i:s', $version);

