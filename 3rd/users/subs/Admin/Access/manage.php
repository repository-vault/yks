<?

$access_zone = $sub0;
if($access_zone)
    $zone = sql::row("ks_access_zones", compact('access_zone'));


if($action == "zone_manage") try {
    $data = array(
        'access_zone'         => $_POST['access_zone'],
        'access_zone_parent'  => $_POST['access_zone_parent'],
        'zone_descr'          => rte_clean($_POST['zone_descr']),
    );

    if(!$data['access_zone_parent']) //si la zone est racine
        $data['access_zone_parent'] = $data['access_zone'];

    if(!$zone){
        if(sql::row("ks_access_zones", array('access_zone'=>$data['access_zone'])))
            throw rbx::error("Une zone du meme nom existe deja.");

        $res = sql::insert("ks_access_zones", $data);
        if(!$res)
            throw rbx::error("&err_sql;");


        rbx::ok("Une nouvelle zone {$data['access_zone']} a été créée");
        jsx::js_eval("Jsx.open('/?$href_fold/list', 'access_zone_box',this)");
        jsx::$rbx = false;
    } else {

        sql::replace("ks_access_zones", $data, compact('access_zone'));

        rbx::ok("Modification enregistrées");
        jsx::js_eval(jsx::PARENT_RELOAD);

    }

}catch(rbx $e){rbx::error("Impossible de proceder à l'enregistrement de la zone.");}


