<?php

class authfailed extends _sql_base {
  
  const sql_table = 'ks_auth_ldap_failed';
  const sql_key   = 'auth_ldap_failed_id';
  protected $sql_table = self::sql_table;
  protected $sql_key   = self::sql_key; 

  public static function delete_old($time = 86400){
    sql::delete(self::sql_table, array('failed_date < '.(time()-$time)));
  }

  public static function delete($data = true){
    sql::delete(self::sql_table, $data);
  }
  
  public static function count($ip = NULL, $login = NULL, $other = NULL){
    
    if(isset($ip))
      $data['failed_ip'] = $ip;
      
    if(isset($login))
      $data['failed_login'] = $login;
    
    if(isset($other))
      $data[] = $other;
      
    return sql::row(self::sql_table, $data, 'count('.self::sql_key.') AS nb');
  }
  
  public function add($ip, $login, $time){
    $data = array(
    'failed_ip'    => $ip,
    'failed_login' => $login,
    'failed_date'  => $time,
    );
   sql::insert(self::sql_table, $data);
  }
  
   public function default_get($fields = NULL, $where = NULL, $extras = null){      
    if(!isset($fields)){
      $fields = array(
        'count('.self::sql_key.')',
        'failed_login',
        'failed_ip',
      );
    }
    
    $extras = 'GROUP BY failed_login, failed_ip ORDER BY failed_login';
    
    sql::select(self::sql_table, true, implode($fields, ', '), $extras);
    
    return sql::brute_fetch();
     
  }
  
  public function get($fields = NULL, $where = true, $extras = null){    
    sql::select(self::sql_table, $where, $fields, $extras);
    
    return sql::brute_fetch();
  }
}
