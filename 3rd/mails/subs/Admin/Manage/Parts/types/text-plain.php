<?php

if($action == "part_update") try {
    $data=array(
        'part_contents'=>trim($_POST['part_contents'])
    );

    sql::update("ks_mails_parts",  $data, $verif_part);
    rbx::ok("Enregistrement ok");


}catch(rbx $e){}