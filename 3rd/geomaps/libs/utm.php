<?

//universal transverse mercator
class geo_utm {
  static function init(){
    $dir = dirname(__FILE__);
    require_once "$dir/geodetictoutm.php";
  }

  function geodetic_to_utm_map( $w, $h, $coords, $zero_x = null, $zero_y = null) {
    if(is_null($zero_x)) $zero_x = floor($w/2);
    if(is_null($zero_y)) $zero_y = floor($h/2);

    list($lat, $lon) = array($coords['lat'], $coords['lon']);
    //cli::box('initials', compact('zero_x', 'zero_y', 'w', 'h', 'lat', 'lon'));

    list($x, $y, $zone) = geodetictoutm($lat, $lon);
    $ay =  $lat/180;

    $area_w = $w/60;
    $area_h = $h/20;

    //$scale_y = 130/3322576;      //    30° = 3322576n = 130 px on map

    $zone_width = 40075.16/60 * 1000;
    $zone_height = 40008.00 / 20 * 1000;

    $X = ($zone-1 + $x/$zone_width) * $area_w;
    $Y = $zero_y - ($h*$ay);

    $out = array($X, $Y);
    //cli::box("Result", array($X, $Y, $ay));
    return $out;
  }

}