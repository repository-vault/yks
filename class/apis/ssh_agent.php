<?php
/**
* #Motivation
* Expose your ssh-agent features :
* - list all identities
* - sign any message for a requested identity
* - add identity
* #Notes
*  * pagent don't care about mpint
* #Credits
*  * ssh-agent protocol     : http://cvsweb.openbsd.org/cgi-bin/cvsweb/src/usr.bin/ssh/PROTOCOL.agent?rev=HEAD
*  * tcpdump on unix socket : http://superuser.com/q/484671 
* *  mpint format           : https://www.ietf.org/rfc/rfc4251.txt
*/

class ssh_agent_helper {
  const SYSTEM_SSH_AGENTC_REQUEST_IDENTITIES = 11;
  const SYSTEM_SSH_AGENT_IDENTITIES_ANSWER   = 12;

  
  const SYSTEM_SSH_AGENT_SUCCESS             = 6;
  const SYSTEM_SSH_AGENT_FAILURE             = 5;
  const SYSTEM_SSH_AGENTC_SIGN_REQUEST       = 13;
  const SYSTEM_SSH_AGENT_SIGN_RESPONSE       = 14;

  const SYSTEM_SSH2_AGENTC_ADD_IDENTITY      = 17;
  const SSH2_AGENTC_REMOVE_IDENTITY          = 18;
  const SSH2_AGENTC_REMOVE_ALL_IDENTITIES    = 19;

  var $lnk;

  function __construct($SOCK = null){
    if(is_null($SOCK))
      $SOCK = getenv("SSH_AUTH_SOCK");
    $this->lnk = "unix://$SOCK";
  }

  private function getlnk(){
    return fsockopen($this->lnk);
  }

  public static function create(){
    $cmd = "eval $(ssh-agent) > /dev/null ; echo \$SSH_AGENT_PID\;\$SSH_AUTH_SOCK;";
    list($SSH_AGENT_PID, $SSH_AUTH_SOCK) = explode(";", trim(shell_exec($cmd)));
    register_shutdown_function("shell_exec", "kill $SSH_AGENT_PID"); //posix_kill dont want to kill (??)
    return $SSH_AUTH_SOCK;
  }

  function add_key($pkey, $comment = ""){

    $this->prikey_id = openssl_get_privatekey($pkey);
    if(!$this->prikey_id)
        throw new Exception("Error while loading rsa keys ");

    $ppk_infos = openssl_pkey_get_details($this->prikey_id);
    $details   = $ppk_infos['rsa'];

    $algo = "ssh-rsa";


    $packet = pack('CNa*a*a*a*a*a*a*Na*', self::SYSTEM_SSH2_AGENTC_ADD_IDENTITY,
        strlen($algo),            $algo,
        self::mpint($details['n']),
        self::mpint($details['e']),
        self::mpint($details['d']),
        self::mpint($details['iqmp']),
        self::mpint($details['p']),
        self::mpint($details['q']),
        strlen($comment),         $comment);
    $packet = pack('Na*', strlen($packet), $packet);

    $lnk = $this->getlnk();
    $data = fwrite($lnk, $packet);

    $length = $this->read($lnk, 'N');
    $type   = $this->read($lnk, 'c');
    if ($type != self::SYSTEM_SSH_AGENT_SUCCESS)
      throw new Exception("Invalid ssh-agent sign response ($type)");

    fclose($lnk);
  }

  function remove_key($pubkey) {

    $packet = pack("CNa*", self::SSH2_AGENTC_REMOVE_IDENTITY,
        strlen($pubkey), $pubkey
    );
    $packet = pack('Na*', strlen($packet), $packet);

    $lnk = $this->getlnk();
    fwrite($lnk, $packet);

    $length = $this->read($lnk, 'N');
    $type   = $this->read($lnk, 'c');

    if ($type != self::SYSTEM_SSH_AGENT_SUCCESS)
      throw new Exception("Invalid ssh-agent response ($type)");

    fclose($lnk);
  }


  function remove_all_keys() {
    $packet = pack("C", self::SSH2_AGENTC_REMOVE_ALL_IDENTITIES);
    $packet = pack('Na*', strlen($packet), $packet);

    $lnk = $this->getlnk();
    fwrite($lnk, $packet);

    $length = $this->read($lnk, 'N');
    $type   = $this->read($lnk, 'c');

    if ($type != self::SYSTEM_SSH_AGENT_SUCCESS)
      throw new Exception("Invalid ssh-agent response ($type)");

    fclose($lnk);
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

    $lnk = $this->getlnk();
    fwrite($lnk, $packet);

    $length = $this->read($lnk, 'N');
    $type   = $this->read($lnk, 'c');
    if ($type != self::SYSTEM_SSH_AGENT_SIGN_RESPONSE)
      throw new Exception("Invalid ssh-agent sign response");

    $signature_blob = fread($lnk, $length - 1);
    fclose($lnk);
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
    $packet = pack("C", self::SYSTEM_SSH_AGENTC_REQUEST_IDENTITIES);
    $packet = pack('Na*', strlen($packet), $packet);

    $lnk = $this->getlnk();
    fwrite($lnk, $packet);

    $length = $this->read($lnk, 'N');
    $type   = $this->read($lnk, 'c');

    if ($type != self::SYSTEM_SSH_AGENT_IDENTITIES_ANSWER)
      throw new Exception("Invalid ssh-agent key list response");

    $count  = $this->read($lnk, 'N');

    $keys = array();
    for($i=0;$i<$count; $i++) {
      $key_blob    = fread($lnk, $this->read($lnk, 'N'));
      $key_comment = fread($lnk, $this->read($lnk, 'N'));
      $key_type = substr($key_blob, 4, current(unpack('N', $key_blob)));
      $key_fingerprint = md5($key_blob);
      $keys[$key_fingerprint] = compact('key_type', 'key_comment', 'key_fingerprint', 'key_blob');
    }

    return $keys;
  }

  private static function mpint($b){
    if(ord($b) >= 128) //MSB is set, but all values are positives, prepend a null byte
      $b = "\0" . $b;
    return pack("Na*", strlen($b), $b);
  }

  //minimal helper
  private function read($lnk, $type){
    $length = $type == "N" ? 4 : 1;
    return first(unpack($type, fread($lnk, $length)));
  }

}