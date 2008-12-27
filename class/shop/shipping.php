<?

class shipping {
  public static $shipping;
  public static $zones;
  static function init(){
	sql::query("SELECT *
		FROM `ks_ecommerce_shipping`
		ORDER BY shipping_type, shipping_price DESC
	");$shipping=array();
	while(extract($l=sql::fetch())) $shipping[$shipping_type][]=$l;
	self::$shipping=$shipping;

	
	$zones=array();
	sql::query("SELECT * FROM `ks_geo_zones`");
	while(extract(sql::fetch()))$zones[$country_code][]=$geo_zone;
	self::$zones=$zones;

  }

  static function get($shipping_type,$addr){
	$cost=0;
	foreach((array)self::$shipping[$shipping_type] as $tmp){
		$type=$tmp['zone_type'];$val=$tmp['zone_geo'];$country_code=$addr['country_code'];
		if($addr[$type]==$val
		    ||  ( $type=="geo_zone"
			  && $country_code
			  &&  in_array($val,self::$zones[$country_code])
			) ) $cost =$tmp['shipping_price'];
	} return $cost;
  }
} shipping::init();

