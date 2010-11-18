<?
class taxes {
  public static $taxes;
  static function init(){
	sql::select("ks_ecommerce_taxes");$taxes=array();
	while(extract(sql::fetch())) $taxes[$tax_type][$zone_type][$zone_geo]=$tax_rate;
	self::$taxes=$taxes;
  }

  static function get($tax_type,$addr){
	//return the tax rate based on local position
	$rate = products::get(self::$taxes,$tax_type,$addr);
	return $rate;
  }
}


