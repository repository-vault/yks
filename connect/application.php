<?php
namespace yks\connect;
use \crypt;
use \Exception;

//yks connect server application instance
class application {

  private $public_key;
  private $application_name;
  public $application_id; //public key fingerprint

  function __construct($public_key_path) {
    $this->application_name = basename($public_key_path);
    $local_key = file_get_contents($public_key_path); //openssh format
    $this->application_id   = crypt::GetFingerPrint($local_key);

    $local_key = crypt::BuildPemKey(crypt::openssh2pem($local_key), crypt::PEM_PUBLIC);
    $this->public_key = openssl_pkey_get_public($local_key);

    if(!$this->public_key)
      throw new Exception("Invalid public key");
  }

  function verify($msg, $signature) {
    $signature = base64_decode($signature);

    if(!openssl_verify($msg, $signature, $this->public_key))
      throw new Exception("Invalid signature");
    return true;
  }

  function seal($data){
    return crypt::pem_seal($this->public_key, $data);
  }

}

