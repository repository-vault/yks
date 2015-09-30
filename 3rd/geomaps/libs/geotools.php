<?php

class geotools {

  const cache_table = 'ks_geodecode_cache';
  const OVER_QUERY_LIMIT = 'OVER_QUERY_LIMIT';
  const NO_RESULT = 'NO_RESULT';
  const OK = 'OK';
  const CACHED = 'CACHED';

  static function init(){
    $dir = dirname(__FILE__);
    require_once "$dir/geodetictoutm.php";
    classes::register_class_path("geo_utm", "$dir/utm.php");
  }


  /**
  * Geocode a given addr, return gps latitude and longitude.
  *
  * @param array $addr_infos address info
  * @param mixed $addr_hash hash for cache lookup
  * @param mixed $bypass_cache force the regeneration
  */
  public static function geodecode_addr($addr_infos, $addr_hash = false, $bypass_cache=false) {

    //$bypass_cache = $addr_hash === true; //debug
 DebugBreak("1@172.19.21.33");
    $addr_filter = array(
      'street_number',
      'route',
      'postal_code',
      'locality',
      'area_level_2',
      'area_level_1',
      'country',
    );
    $addr_data = array_sort($addr_infos, $addr_filter);
    $addr_data = array_filter(array_map('trim',$addr_data));

    // build addr_str (add area_level ?)
    $addr_str .= $addr_data['street_number']?"{$addr_data['street_number']} ":'';
    $addr_str .= $addr_data['route']?"{$addr_data['route']}, ":'';
    $addr_str .= $addr_data['postal_code']?"{$addr_data['postal_code']} ":'';
    $addr_str .= $addr_data['locality']?"{$addr_data['locality']}, ":'';
    $addr_str .= $addr_data['country']?"{$addr_data['country']}":'';

    if(!$addr_hash)
      $addr_hash = strtolower(md5(strtoupper($addr_str))); // hash the strtoup for better cache optimization

    $verif_hash = array('geodetic_hash' => $addr_hash);
    $cached = sql::row(self::cache_table, $verif_hash);
    if($cached && !$bypass_cache) {
      return array(
        'geodetic_lat' => $cached['geodetic_lat'],
        'geodetic_lon' => $cached['geodetic_lon'],
        'return_code'  => geotools::CACHED,
      );
    }

    $data = array(
      'geodetic_hash' => $addr_hash,
      'geodetic_addr' => $addr_str,
    );
    $result = array(
      'geodetic_lat' => null,
      'geodetic_lon' => null,
      'return_code'  => geotools::OK,
    );
    try {
      $geodetic = self::geodecode_request($addr_str);
      $result['geodetic_lat'] = $geodetic['lat'];
      $result['geodetic_lon'] = $geodetic['lon'];
    } catch(Exception $e){
      if($e->getMessage() == geotools::OVER_QUERY_LIMIT)
        $result['return_code'] = geotools::OVER_QUERY_LIMIT; // to recompute later
      else
        $result['return_code'] = geotools::NO_RESULT;
    }

    // Update cache
    sql::insert("ks_geodecode_cache", array_merge($data, $result));

    return $result;
  }

  /**
  * Get latittude, longitude from address
  *
  * @param string $addr formatted addresse
  * @return array
  * @throws Exception if over query limit or Invalide Response
  */
  public static function geodecode_request($addr_str){

    $url = 'http://maps.googleapis.com/maps/api/geocode/json?';

    $param = array(
      'address' => "$addr_str",
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
