<?

class geotools {

  const cache_table = 'ks_geodecode_cache';

  static function init(){
    $dir = dirname(__FILE__);
    require_once "$dir/geodetictoutm.php";
    classes::register_class_path("geo_utm", "$dir/utm.php");
  }


  static function geodecode_addr($addr_infos, $addr_hash = null){

    try {
      $data = array(
          'street'  => $addr_infos['addr_field1'],
          'city'    => $addr_infos['addr_city'],
          'country'  => $addr_infos['country_code'],
          //'state'=>city=Sunnyvale&state=CA";        
      ); $data_str = http_build_query($data);

      if(!$addr_hash)
        $addr_hash = substr(md5($data_str),0,5);

      $verif_hash = array('geodetic_hash' => $addr_hash);
      $cached = sql::row(self::cache_table, $verif_hash);

      if($cached) {
        $data = array(
          'lat' => $cached['geodetic_lat'],
          'lon' => $cached['geodetic_lon']
        );
        return $data;
      }

      $data['appid'] = 'YD-9G7bey8_JXxQP6rxl.fBFGgCdNjoDMACQA--';
      $data = http_build_query($data);


      $url_base =  "http://local.yahooapis.com/MapsService/V1/geocode";
      $query_url = "$url_base?$data";

      $response_str = file_get_contents($query_url);
      //echo $query_url.CRLF.$response_str;die;

      $xml = simplexml_load_string($response_str);

      if(!$xml->Result)
        throw new Exception("Invalid response");

      $lat = (float)$xml->Result->Latitude;
      $lon = (float)$xml->Result->Longitude;

      $data = array(
        'geodetic_hash' => $addr_hash,
        'geodetic_addr' => $data_str,
        'geodetic_lat'  => $lat,
        'geodetic_lon'  => $lon,
      ); sql::insert("ks_geodecode_cache", $data);

    } catch(Exception $e){

    }

    return compact('lat', 'lon');
  }

}