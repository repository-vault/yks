<?

namespace yks\connect;
use \crypt;
use \Exception;

class client {

  /** config must provide :
  * @private_key : INLINE openssh privatekey
  * @token_api   : remote endpoint for yks\connect token delivery
  * @login_api   : remote endpoint for yks\connect login process
  */

  private $token_api;
  private $login_api;

  private $private_key;
  private $client_id; //private key's fingerprint

  function __construct($config){

    $this->token_api = $config['token_api'];
    $this->login_api = $config['login_api'];

    $local_key       = crypt::BuildPemKey($config['private_key'], crypt::PEM_PRIVATE);
    $this->client_id = crypt::GetFingerPrint(crypt::BuildAuthorizedKey($local_key));
    $this->private_key = openssl_get_privatekey($local_key);

    if(!$this->private_key)
      throw new Exception("Invalid private key");
  }

  public function payload_open($payload){
    return crypt::pem_open($this->private_key, $payload);
  }

/**
* forge an application login endpoint URL on remote yks\connect endpoint
* @param string $application_ctl : local yks\connect ctl
* @returns     the remote application endpoint for yks\connect login process with current application credentials
*/
   public function get_login_url($application_ctl){

    $code = file_get_contents($this->token_api);

    if(!openssl_sign($code, $signature_check, $this->private_key))
      throw new Exception("No signature generated");

    $auth_token     = base64_encode($signature_check);

    $data  = array(
      'redirect_url'   => $application_ctl,
      'application_id' => $this->client_id,
      'auth_token'     => urlencode($auth_token), //http build_query mess with +
      'code'           => $code,
    );
    $redirect_url = $this->login_api.http_build_query($data, '', '&');

    return $redirect_url;
  }
}
