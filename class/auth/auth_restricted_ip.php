<?
/*	"Yks auth_restricted_ip" by Leurent F. (131)
	distributed under the terms of GNU General Public License - © 2007 
*/
define('AUTH_RESTRICTED_ID',false);

class auth_restricted_ip {
  static function reload(){
	sess::$sess['session_ip']=$_SERVER['REMOTE_ADDR'];
	if(!AUTH_RESTRICTED_ID) return true;
	return auth::update(self::verif());
  }

  static function verif($user_id=false){
	$ip=ip2int(sess::$sess['session_ip']);
	$tmp=sql::qrow("SELECT user_id
		FROM `ks_auth_restricted_ip`
		INNER JOIN `ks_users_list` USING(user_id)
		WHERE 
			FIND_IN_SET('auth_restricted_ip',auth_type)
			&& '$ip' BETWEEN ip_range_start AND ip_range_end
		LIMIT 1");
	return $user_id?($tmp['user_id']==$user_id):$tmp['user_id'];
  }

}
              