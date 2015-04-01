<?php
if($action=="part_update")try {
    $part_contents = txt::rte_clean($_POST['part_contents']);

    //text might contain untranslated entities, but it's OKAY !

    $valid_mask = "#&amp;([a-z0-9_.]+);#i";
    $part_contents = preg_replace($valid_mask, "&\\1;", $part_contents);

    $data = compact('part_contents');
   
    sql::update("ks_mails_parts",  $data, $verif_part);
    rbx::ok("Enregistrement ok");


}catch(rbx $e){}