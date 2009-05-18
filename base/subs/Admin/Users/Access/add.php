<?

if($action == "zone_add") try {
    $data = array(
        'access_zone'=>$_POST['access_zone'],
        'access_zone_parent'=>$_POST['access_parent'],
        'zone_descr'=>rte_clean($_POST['zone_descr']),
    );
    if(!$data['access_zone_parent']) //si la zone est racine
        $data['access_zone_parent'] = $data['access_zone'];

    if(sql::row("ks_access_zones", array('access_zone'=>$data['access_zone'])))
        throw rbx::error("Une zone du meme nom existe deja.");

    $res= sql::insert("ks_access_zones", $data);
    if(!$res)
        throw rbx::error("&err_sql;");


    rbx::ok("Une nouvelle zone {$data['access_zone']} a été créée");
    jsx::js_eval("Jsx.open('/?$href_fold/list', 'access_zone_box',this)");
    jsx::$rbx = false;
}catch(rbx $e){rbx::error("Impossible de proceder à la création de la nouvelle zone.");}

