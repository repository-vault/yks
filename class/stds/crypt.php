<?

class crypt {
  
  
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
    $private_key = self::cleanupPem($contents);
    $public_key  = self::cleanupPem($ppk_infos['key']);

    return compact('private_key', 'public_key');
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
  
  private static function cleanupPem($key){
    $cleanup = array(
      '#^-+(BEGIN|END)( RSA)? (PUBLIC|PRIVATE) KEY-+$#m' => '',
      "#\r?\n#" => ''
    );
    return preg_areplace($cleanup, $key);
  }
  
  
  private static function cypherInit($passphrase, $raw = false){
    $algo    = MCRYPT_RIJNDAEL_128;
//    $algo    = MCRYPT_DES;

    $cipher  = mcrypt_module_open($algo, '', MCRYPT_MODE_CBC, '');
    
    // Don't touch the pass pharse, i know what i'm doing
    $key256 = $raw ? md5($passphrase) : $passphrase;

    $iv_size  = mcrypt_enc_get_iv_size($cipher);
    $key_size = mcrypt_enc_get_key_size($cipher);

    if(strlen($key256) > $key_size && $raw)
      throw new Exception("The raw passphrase is too large and would be truncated.");
    
    $key    = substr($key256, 0, $key_size);
    $iv     = substr($key256, 0, $iv_size);

    mcrypt_generic_init($cipher, $key, $iv);
    
    return $cipher;
  }


  static function encrypt($clearText, $passphrase, $out64 = false, $raw = false) {
    $cipher  = self::cypherInit($passphrase, $raw);
    
    $cipherText = mcrypt_generic($cipher, $clearText);
    if($out64) $cipherText = base64_encode($cipherText);
    return $cipherText;
  }

  static function decrypt($cipherText, $passphrase, $in64 = false, $raw = false) {
    $cipher  = self::cypherInit($passphrase, $raw);

    if($in64) $cipherText = base64_decode($cipherText);
    $cleartext = mdecrypt_generic($cipher, $cipherText);
    if($in64) $cleartext = rtrim($cleartext, "\0");
    return $cleartext ;
  }

  //Use streams for this function
  function crypt_file($input_stream, $output_stream, $passphrase, $bufferSize = 8192, $token = '='){

    // Use ONE cypher
    $cypher = crypt::cypherInit($passphrase); 

    $offset = strlen($token);

    while(!feof($input_stream)){
      $buffer = fread($input_stream, $bufferSize - $offset);
      if(!$buffer)
        break;
      $bufferCrypted = mcrypt_generic($cypher, $buffer.$token);
      
      $bCSize = strlen($bufferCrypted);
      if($bCSize != $bufferSize && !feof($input_stream))
        throw new Exception("Encrypted Buffer size mismatch ($bCSize != $bufferSize).");
      
      fwrite($output_stream, $bufferCrypted);
    }
    
    fclose($output_stream);
    fclose($input_stream);
  }
  
  //Use streams for this function
  function decrypt_file($input_stream, $output_stream, $passphrase, $bufferSize = 8192, $token = '='){

    // Use ONE cypher
    $cypher = crypt::cypherInit($passphrase);

    $offset = strlen($token);

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
      
      fwrite($output_stream, $bufferDecrypted);
    }
    
    fclose($output_stream);
    fclose($input_stream);
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
  
  public static function BuildPemKey($key, $type=crypt::PEM_PUBLIC) {
    if($type == crypt::PEM_PRIVATE){
      $keyMask = "-----BEGIN RSA PRIVATE KEY-----\n%s\n-----END RSA PRIVATE KEY-----"; 
    }else{
      $keyMask = "-----BEGIN PUBLIC KEY-----\n%s\n-----END PUBLIC KEY-----";   
    }

    return sprintf($keyMask, trim(chunk_split($key, 64, "\n")) );
  }
}