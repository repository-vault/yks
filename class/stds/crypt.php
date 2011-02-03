<?

class crypt {

  private static function cypherInit($passphrase){
    $algo    = MCRYPT_RIJNDAEL_128;
//    $algo    = MCRYPT_DES;

    $cipher  = mcrypt_module_open($algo, '', MCRYPT_MODE_CBC, '');
    $key256 = md5($passphrase);

    $iv_size  = mcrypt_enc_get_iv_size($cipher);
    $key_size = mcrypt_enc_get_key_size($cipher);

    $key    = substr($key256, 0, $key_size);
    $iv     = substr($key256, 0, $iv_size);

    mcrypt_generic_init($cipher, $key, $iv);
    return $cipher;
  }


  static function encrypt($clearText, $passphrase, $out64 = false) {
    $cipher  = self::cypherInit($passphrase);
    //$clearText = self::pad($clearText);

    $cipherText = mcrypt_generic($cipher, $clearText);
    if($out64) $cipherText = base64_encode($cipherText);
    return $cipherText;
  }

  static function decrypt($cipherText, $passphrase, $in64 = false) {
    $cipher  = self::cypherInit($passphrase);

    if($in64) $cipherText = base64_decode($cipherText);
    $cleartext = mdecrypt_generic($cipher, $cipherText);
    if($in64) $cleartext = rtrim($cleartext, "\0");
    return $cleartext ;
  }

  const PEM_PUBLIC = "public";
  const PEM_PRIVATE = "private";
   
  public static function BuildPemKey($key, $type=crypt::PEM_PUBLIC) {
    if($type == crypt::PEM_PRIVATE){
      $keyMask = "-----BEGIN RSA PRIVATE KEY-----\n%s\n-----END RSA PRIVATE KEY-----"; 
    }else{
      $keyMask = "-----BEGIN PUBLIC KEY-----\n%s\n-----END PUBLIC KEY-----";   
    }

    $k = array();
    $lineSize = 65;
    for($i = 0; $i < strlen($key); $i += $lineSize)
      $k[] = substr($key, $i, $lineSize);

    return sprintf($keyMask, implode("\n", $k));
  }
}