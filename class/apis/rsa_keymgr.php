<?php

class rsa_keymgr {
  private $prikey_id;
  private $pubkey_id;
  private $private_path;
  private $prikey_content;

  private $pubkey_content;

  function __construct($file_path = false){
    $this->file_path = $file_path;
    if($this->file_path)
      $this->load(file_get_contents($file_path));
  }
  
  public function load($private_key){
    $this->prikey_content = $private_key;

    $this->prikey_id = openssl_get_privatekey($this->prikey_content);
    if(!$this->prikey_id)
        throw new Exception("Error while loading rsa keys ");

    $ppk_infos = openssl_pkey_get_details($this->prikey_id);
    $this->pubkey_id       = openssl_get_publickey($ppk_infos['key']);
    $this->pubkey_content  = crypt::cleanupPem($ppk_infos['key']);
  }

  public static function from_pk($content){
    $out = new self();
    $out->load($content);
    return $out;
  }


  function sign($str) {
    unset($out_signature);//ref
    openssl_sign($str, $out_signature, $this->prikey_id);
    return base64_encode($out_signature);
  }

  function verify($str, $signature){
    $signature = base64_decode($signature);

      $res = openssl_verify($str, $signature, $this->pubkey_id);
      if($res !=1 )
        return false;
      return true;
  }


  function extract_pubkey(){
    return $this->pubkey_content;
  }

  function extract_comment(){
    $body = crypt::cleanupPem($this->prikey_content);

    $comment = preg_reduce("#^-- Comment: (.*?)$#m", $this->prikey_content);
    $hmac    = preg_reduce("#^-- Private-MAC: (.*?)$#m", $this->prikey_content);

    if($this->verify($comment, $hmac))
      return $comment;


    return null;
  }

  function write_comment($comment){
    $body = crypt::cleanupPem($this->prikey_content);
    $str =  crypt::BuildPemKey($body, crypt::PEM_PRIVATE);
    $str .="\n";
    $sign = $this->sign($comment);
    $str .="-- Comment: $comment\n";
    $str .="-- Private-MAC: $sign\n";
    if($this->file_path)
      file_put_contents($this->file_path, $str);
    return $str;
  }

}
