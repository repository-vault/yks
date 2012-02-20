<?

class rsa_keymgr {
  private $prikey_id;
  private $pubkey_id;
  private $private_path;
  private $prikey_content;

  function __construct($file_path){
    $this->file_path = $file_path;
    $this->prikey_content = file_get_contents($file_path);

    $this->prikey_id = openssl_get_privatekey($this->prikey_content);
    if(!$this->prikey_id)
        throw new Exception("Error while loading rsa keys ");

    $ppk_infos = openssl_pkey_get_details($this->prikey_id);
    $this->pubkey_id       = openssl_get_publickey($ppk_infos['key']);
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
    file_put_contents($this->file_path, $str);
    return $str;
  }

}
