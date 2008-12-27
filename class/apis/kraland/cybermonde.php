<?


function get_kra_city($flag){
    $player_countries = vals(yks::$get->types_xml->player_country);
    $repart_cyber=$provinces=$villes=array();
    $repart_cyber['empire']['provinces']=array();

    foreach($player_countries as $player_country){
        $file=KRA_JS_CACHE_DIR."/$player_country.js";
        if(!is_file($file)) continue;
        $str= utf8_encode(file_get_contents($file));
        preg_match_all("#provinces\[([0-9]{1,3})\] = \"(.*?)\"#",$str,$prov);unset($prov[0]);
        preg_match_all("#cities\[([0-9]{1,3})\] = \"(.*?)\"#",$str,$cities);unset($cities[0]);

        $villes+=array_combine($cities[1],$cities[2]);
	$provinces+=array_combine($prov[1],$prov[2]);

	$repart_cyber['provinces']['empire'][$a]=$prov[1];
	$repart_cyber['empire']['provinces']+= array_combine($prov[1],array_fill(0,count($prov[1]),$a));
    } asort($villes);asort($provinces);


    data::store("kraland_cities", $villes);
    data::store("kraland_states", $provinces);

    if($flag == "kraland_cities") return $villes;
    elseif($flag=="kraland_states") return $provinces;
}
