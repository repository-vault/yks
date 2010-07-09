<?

class geotools {

  const cache_table = 'ks_geodecode_cache';

  static function init(){
    $dir = dirname(__FILE__);
    require_once "$dir/geodetictoutm.php";
    classes::register_class_path("geo_utm", "$dir/utm.php");
  }


  public static function geodecode_addr($addr_infos, $addr_hash = false) {

    $bypass_cache = $addr_hash === true; //debug

    $addr = array(
      'street'  => $addr_infos['addr_field1'],
      'city'    => $addr_infos['addr_city'],
      'zip'     => $addr_infos['addr_zipcode'],
      'country' => $addr_infos['country_code'],
      'state'   => $addr_infos['addr_state'],
    ); $data_str = http_build_query($addr);

    if($bypass_cache) {
        error_log("Georequest bypassing cache !");
        return self::geodecode_request($addr);
    }

    if(!$addr_hash)
        $addr_hash = substr(md5($data_str),0,5);

    $verif_hash = array('geodetic_hash' => $addr_hash);
    $cached = sql::row(self::cache_table, $verif_hash);

    if($cached)
        return array(
            'lat' => $cached['geodetic_lat'],
            'lon' => $cached['geodetic_lon']
        );

    try {
        $geodetic = self::geodecode_request($addr);
    } catch(Exception $e){
        throw new Exception("Failure");
    }

        //update cache
    $data = array(
        'geodetic_hash' => $addr_hash,
        'geodetic_addr' => $data_str,
        'geodetic_lat'  => $geodetic['lat'],
        'geodetic_lon'  => $geodetic['lon'],
        'resolution_score' => $geodetic['quality'],
    ); sql::insert("ks_geodecode_cache", $data);

    return $geodetic;
  }

  private static function geodecode_request($addr){

    $data = array(
      'street'  => $addr['street'],
      'city'    => $addr['city'],
      'postal'  => $addr['zip'],
      'country' => $addr['country'],
      'state'   => $addr['state'],
    ); array_filter($data);

    $data['appid'] = 'YD-9G7bey8_JXxQP6rxl.fBFGgCdNjoDMACQA--';
    $data['appid'] = yks::$get->config->apis->geodecode['key'];


    $data['flags'] = 'C';

    $data = http_build_query($data);

    $url_base =  "http://where.yahooapis.com/geocode";
    $query_url = "$url_base?$data";

    $response_str = strtolower(file_get_contents($query_url));
    //echo $query_url.CRLF.$response_str;die;

    $xml = simplexml_load_string($response_str);

    if(!$xml->result)
    throw new Exception("Invalid response");

    $quality = (int)$xml->result->quality;

    $lat = (float)$xml->result->latitude;
    $lon = (float)$xml->result->longitude;
    return compact('lat', 'lon', 'quality');
  }

}