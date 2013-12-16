<?

class crypt {
  const ASN_LONG_LEN  = 0x80;
  

  public static function pwgen($length){
    return substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', $length)), 0, $length);
  }

  public static function getppkpair($rsa_options = array()){
    $options = array(
      'digest_alg'       => 'sha1',
      'private_key_bits' => 2048,
      'private_key_type' => OPENSSL_KEYTYPE_RSA,
      'encrypt_key'      => false,
    ); $options = array_merge($options, $rsa_options);
    
    $ppk       = openssl_pkey_new($options);
    $ppk_infos = openssl_pkey_get_details($ppk);
    if(!openssl_pkey_export ($ppk, $contents))
        throw new Exception("Fail to export private key");
    $private_key     = self::cleanupPem($contents);
    $public_key      = self::cleanupPem($ppk_infos['key']);
    $private_openssh = self::pem2openssh($public_key);
    return compact('private_key', 'public_key', 'private_openssh');
  }
  
  public static function ExtractPublicFromPrivateKey($private_key){
    
    //Get private id
    $private_key = crypt::BuildPemKey($private_key, crypt::PEM_PRIVATE);
    $openssl_priv = openssl_get_privatekey($private_key);
    if(!$openssl_priv)
      throw new Exception("Invalid private key.");
    
    //Get details
    $key_details = openssl_pkey_get_details($openssl_priv);
    if(!$key_details)
      throw new Exception("Invalid public key.");

    openssl_pkey_free($openssl_priv);

    return self::cleanupPem($key_details['key']);
  }
  
  
  private static function cypherInit($passphrase, $raw = false){
    $algo    = MCRYPT_RIJNDAEL_128;
//    $algo    = MCRYPT_DES;

    $cipher  = mcrypt_module_open($algo, '', MCRYPT_MODE_CBC, '');
    
    // Don't touch the pass pharse, i know what i'm doing
    $key256 = $raw ? $passphrase : md5($passphrase);

    $iv_size  = mcrypt_enc_get_iv_size($cipher);
    $key_size = mcrypt_enc_get_key_size($cipher);

    if(strlen($key256) > $key_size && $raw)
      throw new Exception("The raw passphrase is too large and would be truncated.");
    
    $key    = substr($key256, 0, $key_size);
    $iv     = substr($key256, 0, $iv_size);

    mcrypt_generic_init($cipher, $key, $iv);
    
    return $cipher;
  }


  static function encrypt($clearText, $passphrase, $out64 = false) {
    $cipher  = self::cypherInit($passphrase);
    
    $cipherText = mcrypt_generic($cipher, $clearText);
    if($out64) $cipherText = base64_encode($cipherText);
    return $cipherText;
  }

  static function decrypt($cipherText, $passphrase, $in64 = false) {

    $cipher  = self::cypherInit($passphrase);
    if($in64) $cipherText = base64_decode($cipherText);
    if(!$cipherText )
      return "";

    $cleartext = mdecrypt_generic($cipher, $cipherText);
    if($in64) $cleartext = rtrim($cleartext, "\0");
    return $cleartext ;
  }

  //Use streams for this function
  function crypt_stream($input_stream, $output_stream, $passphrase, $bufferSize = 8192, $token = '='){

    // Use ONE cypher
    $cypher = crypt::cypherInit($passphrase); 

    $offset = strlen($token);

    //Hash while reading
    $hasher = hash_init('md5');
    
    while(!feof($input_stream)){
      $buffer = fread($input_stream, $bufferSize - $offset);
      if(!$buffer)
        break;
      $bufferCrypted = mcrypt_generic($cypher, $buffer.$token);
      
      $bCSize = strlen($bufferCrypted);
      if($bCSize != $bufferSize && !feof($input_stream))
        throw new Exception("Encrypted Buffer size mismatch ($bCSize != $bufferSize).");
      
      hash_update($hasher, $bufferCrypted);
      
      fwrite($output_stream, $bufferCrypted);
    }
    
    fclose($output_stream);
    fclose($input_stream);
    
    return hash_final($hasher);
  }
  
  //Use streams for this function
  function decrypt_stream($input_stream, $output_stream, $passphrase, $bufferSize = 8192, $token = '=', $fullOutput = false){

    // Use ONE cypher
    $cypher = crypt::cypherInit($passphrase);

    //Hash while reading
    $hasher = hash_init('md5');
    
    $offset = strlen($token);
    $cpt = 0;
    while(!feof($input_stream)){
      $buffer = fread($input_stream, $bufferSize);
      if(!$buffer)
        break;
      $bufferDecrypted = mdecrypt_generic($cypher, $buffer);
      $bufferDecrypted = rtrim($bufferDecrypted, "\0");

      //Remove token
      if(substr($bufferDecrypted, -$offset) != $token)
        throw new Exception("Token not found !");
      $bufferDecrypted = substr($bufferDecrypted, 0, -$offset);
      if($output_stream)
        fwrite($output_stream, $bufferDecrypted);
      
      hash_update($hasher, $bufferDecrypted);
      
      $cpt += strlen($bufferDecrypted);
    }
    
    if($output_stream)
      fclose($output_stream);
    fclose($input_stream);
    
    $hash = hash_final($hasher);
    if($fullOutput)
      return array(
        'md5' => $hash,
        'size'  => $cpt,
      );
    return $cpt;
  }

  const PEM_PUBLIC = "public";
  const PEM_PRIVATE = "private";
   
  public static function BuildAuthorizedKey($key){
    $tmp_path = files::tmppath();
    file_put_contents($tmp_path, $key);
    chmod($tmp_path, 0600);

    $cmd = "ssh-keygen -f $tmp_path -y";
    exec($cmd, $out);
    return join('', $out);
  }
  


  public static function cleanupPem($key){
    $key = preg_replace("#\r?\n#" , "", $key);
    $key = preg_reduce("#^-+BEGIN(?: RSA)? (?:PUBLIC|PRIVATE) KEY-+(.*?)-+END(?: RSA)? (?:PUBLIC|PRIVATE) KEY-+#m", $key);
    return $key;
  }



  public static function BuildPemKey($key_str, $type=crypt::PEM_PUBLIC) {
    $lines = str_split($key_str, 64);

    if($type == crypt::PEM_PRIVATE){
      array_unshift ($lines, "-----BEGIN RSA PRIVATE KEY-----");
      array_push    ($lines, "-----END RSA PRIVATE KEY-----");
    }else{
      array_unshift ($lines, "-----BEGIN PUBLIC KEY-----");
      array_push    ($lines, "-----END PUBLIC KEY-----");
    }

    return join("\n", $lines);
  }



  private static function ASN_len($s) {
    $len = strlen($s);
    if ($len < self::ASN_LONG_LEN)
        return chr($len);

    $data = dechex($len);
    $data = pack('H*', (strlen($data) & 1 ? '0' : '') . $data);
    return chr(strlen($data) | self::ASN_LONG_LEN) . $data;
  }

  public static function openssh2pem($openssh_data) {
    $data = base64_decode($openssh_data);

    list(,$alg_len)  = unpack('N', substr($data, 0, 4));
    $alg = substr($data, 4, $alg_len);

    if ($alg !== 'ssh-rsa')
        return FALSE;

    list(, $e_len) = unpack('N', substr($data, 4 + strlen($alg), 4));
    $e = substr($data, 4 + strlen($alg) + 4, $e_len);
    list(,$n_len) = unpack('N', substr($data, 4 + strlen($alg) + 4 + strlen($e), 4));
    $n = substr($data, 4 + strlen($alg) + 4 + strlen($e) + 4, $n_len);

    $algid = pack('H*', '06092a864886f70d0101010500');                // algorithm identifier (id, null)
    $algid = pack('Ca*a*', 0x30, self::ASN_len($algid), $algid);                // wrap it into sequence


    $data  = pack('Ca*a*', 0x02, self::ASN_len($n), $n); // numbers
    $data .= pack('Ca*a*', 0x02, self::ASN_len($e), $e);

    $data = pack('Ca*a*', 0x30, self::ASN_len($data), $data);                   // wrap it into sequence
    $data = "\x00" . $data;                                           // don't know why, but needed
    $data = pack('Ca*a*', 0x03, self::ASN_len($data), $data);                   // wrap it into bitstring
    $data = $algid . $data;                                           // prepend algid
    $data = pack('Ca*a*', 0x30, self::ASN_len($data), $data);                   // wrap it into sequence

   return base64_encode($data);
}

  private static function parseASN($string){
    $parsed = array();
    $endLength = strlen($string);
    $bigLength = $length = $type = $dtype = $p = 0;
    while ($p < $endLength) {
        $type = ord($string[$p++]);
        $dtype = ($type & 192) >> 6;
        if ($type==0) continue;
        $length = ord($string[$p++]);
        if (($length & self::ASN_LONG_LEN) == self::ASN_LONG_LEN){
            $tempLength = 0;
            for ($x=0; $x<($length & ( self::ASN_LONG_LEN - 1)); $x++){
                $tempLength = ord($string[$p++]) + ($tempLength * 256);
            }
            $length = $tempLength;
        }
        $data = substr($string, $p, $length);
        $parsed[] = compact('type', 'data');
        $p = $p + $length;
    }
    return $parsed;
  }



  public static function pem2openssh($pem_data){
    $data = base64_decode($pem_data);
    list($base_seq )     = self::parseASN($data);
    list($alg, $numbers) = self::parseASN($base_seq['data']);
    list($number_seq)    = self::parseASN($numbers['data']);
    list($n, $e)         = self::parseASN($number_seq['data']);

    $alg = "ssh-rsa";
    $data = pack("N", strlen($alg)).$alg;
    $data .= pack("N", strlen($e['data'])).$e['data'];
    $data .= pack("N", strlen($n['data'])).$n['data'];

    return $alg." ".base64_encode($data);
  }


}