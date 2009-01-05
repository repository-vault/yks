<?


function get_kra_xml($players_list, $player_country){

    $file = KRA_XML_CACHE_DIR."/$player_country.xml";
    $xml = simplexml_load_file($file);
    if(!$xml) return false;

    $verif_delete = array(
        'player_country'=>$player_country,
        sql::in_join("player_kid", $players_list, 'NOT')
    );

    $delete = sql::delete("kra_players_list", $verif_delete );
    rbx::ok("Delete $player_country : $delete");

    $add=0;
    foreach($xml->channel->item as $item){$add++;
        $player_kid = (int)$item->id;
        $verif_player = compact('player_kid');
        $data=array(
            'player_name'       => $item->name,
            'player_country'  => $player_country,
            'player_avatar'   => $item->link,
        ); sql::replace("kra_players_list", $data, $verif_player);
        //if($player_kid==66407){print_r($data);print_r(end(sql::$queries));die;}
    }
    sql::query("OPTIMIZE TABLE `kra_players_list`");
    
    return compact('add');
}
