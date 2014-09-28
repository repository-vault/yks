<?

/**
* Expose your ssh-agent features :
* - list all identities
* - sign any message for a requested identity
*/

class ssh_agent_helper {
  const SYSTEM_SSH_AGENTC_REQUEST_IDENTITIES = 11;
  const SYSTEM_SSH_AGENT_IDENTITIES_ANSWER   = 12;
  const SYSTEM_SSH_AGENT_FAILURE             = 5;
  const SYSTEM_SSH_AGENTC_SIGN_REQUEST       = 13;
  const SYSTEM_SSH_AGENT_SIGN_RESPONSE       = 14;

  var $lnk;

  function __construct(){
    $SOCK = getenv("SSH_AUTH_SOCK");
    $this->lnk = fsockopen("unix://$SOCK");
  }


  function sign($message, $key_id = null, $key_comment = null){
    $keys = $this->list_keys();
    if($key_comment)
      $key_id = array_search($key_comment, array_column($keys, 'key_comment','key_fingerprint'));
    if(! $key = array_get($keys, "$key_id", first($keys)))
      throw new Exception("Invalid key");
    extract($key);

    if($key_type != 'ssh-rsa')
      throw new Exception("Unsupported key format");

      // the last parameter (currently 0) is for flags and ssh-agent only defines one flag (for ssh-dss): SSH_AGENT_OLD_SIGNATURE
    $packet = pack('CNa*Na*N', self::SYSTEM_SSH_AGENTC_SIGN_REQUEST, strlen($key_blob), $key_blob, strlen($message), $message, 0);
    $packet = pack('Na*', strlen($packet), $packet);
    fwrite($this->lnk, $packet);

    $length = $this->read('N');
    $type   = $this->read('c');
    if ($type != self::SYSTEM_SSH_AGENT_SIGN_RESPONSE)
      throw new Exception("Invalid ssh-agent sign response");

    $signature_blob = fread($this->lnk, $length - 1);
        // the + 12 is for the other various SSH added length fields
    $signature = substr($signature_blob, strlen('ssh-rsa') + 12);

      //check the signature
    $key = sprintf("%s %s", crypt::SSH_RSA, base64_encode($key_blob));
    $key = openssl_pkey_get_public(crypt::BuildPemKey(crypt::openssh2pem($key)));
    $ok = openssl_verify($message, $signature, $key);
    openssl_free_key($key);
    if ($ok !== 1)
      throw new Exception("Bad signature");
    $signature = base64_encode($signature);
    return $signature;
  }

/**
* @alias list
*/
  function list_keys(){
      $request = pack('NC', 1, self::SYSTEM_SSH_AGENTC_REQUEST_IDENTITIES);
    fwrite($this->lnk, $request);

    $length = $this->read('N');
    $type   = $this->read('c');

    if ($type != self::SYSTEM_SSH_AGENT_IDENTITIES_ANSWER)
      throw new Exception("Invalid ssh-agent key list response");

    $count  = $this->read('N');

    $keys = array();
    for($i=0;$i<$count; $i++) {
      $key_blob    = fread($this->lnk, $this->read('N'));
      $key_comment = fread($this->lnk, $this->read('N'));
      $key_type = substr($key_blob, 4, current(unpack('N', $key_blob)));
      $key_fingerprint = md5($key_blob);
      $keys[$key_fingerprint] = compact('key_type', 'key_comment', 'key_fingerprint', 'key_blob');
    }

    return $keys;
  }

  //minimal helper
  private function read($type){
    $length = $type == "N" ? 4 : 1;
    return first(unpack($type, fread($this->lnk, $length)));
  }

}