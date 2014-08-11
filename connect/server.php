<?
namespace yks\connect;
use \Exception;

class server {

  public static function token_forge( $validity = 600){
    $secret = \yks::$get->config->connect['secret'];
    $data   = time() + $validity ;
    $token = hash_hmac('sha1', $data, $secret);
    return sprintf("%s|%s", $data, $token);
  }

  public static function token_verify($data){
    $secret = \yks::$get->config->connect['secret'];
    list($data, $signature) = explode("|", $data, 2);
    $challenge = hash_hmac('sha1', $data, $secret);
    if($challenge != $signature)
        return false;
    return (int)$data > time();
  }

  private static function applications_list(){
    $applications_dir  = "config/connect";
    $applications = array();
    foreach(glob("{$applications_dir}/*.rsa") as $key_path) {
        $application = new application($key_path);
        $applications[$application->application_id] = $application;
    }
    return $applications;
  }

    //search for a private key from a key fingerprint
  function application_lookup($application_id){
    $applications_list = self::applications_list();
    if(!isset($applications_list[$application_id]))
        throw new Exception("Invalid application id");
    return $applications_list[$application_id];
  }

}