<?

class crypt {

  static function encrypt($clearText, $passphrase, $outhex = false) {
    $cipher  = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
    $iv_size = mcrypt_enc_get_iv_size($cipher);

    $key256 = md5($passphrase);
    $iv     = substr($key256, 0, $iv_size);

    mcrypt_generic_init($cipher, $key256, $iv);
    $cipherText = mcrypt_generic($cipher, $clearText);

    if($outhex) $cipherText = bin2hex($cipherText);
    return $cipherText;
  }

  static function decrypt($cipherText, $passphrase, $inhex = false) {
    $cipher  = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
    $iv_size = mcrypt_enc_get_iv_size($cipher);

    $key256 = md5($passphrase);
    $iv     = substr($key256, 0, $iv_size);

    mcrypt_generic_init($cipher, $key256, $iv);
    if($inhex) $cipherText = hex2bin($cipherText);
    $cleartext = mdecrypt_generic($cipher, $cipherText);
    return $cleartext ;
  }
}