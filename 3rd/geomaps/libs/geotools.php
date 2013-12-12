<?

class geotools {

  const cache_table = 'ks_geodecode_cache';
  const OVER_QUERY_LIMIT = 'OVER_QUERY_LIMIT';
  const NO_RESULT = 'NO_RESULT';

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
    ); sql::insert("ks_geodecode_cache", $data);

    return $geodetic;
  }

  /**
  * Get latittude, longitude from address
  *
  * @param mixed $addr
  *
  * <code>
  *   array(
  *     street => value,
  *     city   => value,
  *     postal => value,
  *     state  => value
  *   )
  * </code>
  * @return array
  * @throws Exception if over query limit or Invalide Response
  */
  public static function geodecode_request($addr){

    $data = array(
      'street'  => $addr['street'],
      'city'    => $addr['city'],
      'postal'  => $addr['zip'],
      'state'   => $addr['state'],
    ); array_filter($data);

    $url = 'http://maps.googleapis.com/maps/api/geocode/json?';

    $param = array(
      'address' => "{$addr['street']} {$addr['city']} {$addr['zip']} {$addr['state']}",
      'sensor' => 'false',
    );

    $url = $url.http_build_query($param);

    $response = file_get_contents($url);
    $json = json_decode($response, TRUE);

    if($json['status'] == 'OVER_QUERY_LIMIT')
      throw new Exception(self::OVER_QUERY_LIMIT);

    if($json['status'] != 'OK')
      throw new Exception(self::NO_RESULT);

    $results = first($json['results']);

    $lat = (float)$results['geometry']['location']['lat'];
    $lon = (float)$results['geometry']['location']['lng'];

    return compact('lat', 'lon', 'request');
  }

}
