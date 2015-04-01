<?php


if($action == "zone_delete") try {
    $access_zone = $_POST['access_zone'];
    $verif_zone = compact('access_zone');
    sql::delete("ks_access_zones", $verif_zone);
    rbx::ok("Zone supprimée");
    jsx::$rbx = false;
}catch(rbx $e){}
