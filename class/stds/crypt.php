<?

class crypt {
  const ASN_LONG_LEN  = 0x80;
  const SSH_RSA       = "ssh-rsa";

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
    if(!$ppk)
      throw new Exception("Failed to generate a private key");
    $ppk_infos = openssl_pkey_get_details($ppk);
    if(!openssl_pkey_export ($ppk, $contents))
        throw new Exception("Fail to export private key");

    // On OpenSSL 1.0.X export PEM to traditional format
    if(OPENSSL_VERSION_NUMBER >= 0x010000000){
      $tmp = files::tmppath('key');
      file_put_contents($tmp, $contents);
      $cmd = "openssl rsa -in $tmp 2>/dev/null";
      exec($cmd, $out, $exit);
      unlink($tmp); // cleanup !
      if($exit != 0)
        throw new Exception("Failed to convert private key to traditional format. Missing openssl exec ?");
      $contents = implode(PHP_EOL, $out);
    }

    $private_key     = self::cleanupPem($contents);
    $public_key      = self::cleanupPem($ppk_infos['key']);
    $public_openssh  = self::pem2openssh($public_key);
    $fingerprint     = self::GetFingerPrint($public_openssh);
    $private_openssh = $public_openssh; // historical typo, please remove this asap

    return compact('private_key', 'public_key', 'private_openssh', 'fingerprint', 'public_openssh');
  }

  // Build a signed certificate from a private key.
  // It can use a specified CA cert & private key
  // It's self signed when no CA is given (default)
  public static function BuildCertificate($subject, $private_key_raw, $ca_pkey_raw = null, $ca_cert_raw = null, $serial = null){

    // Open private key
    $private_pem = crypt::BuildPemKey($private_key_raw, crypt::PEM_PRIVATE);
    $private_key = openssl_pkey_get_private($private_pem);
    if(!$private_key)
      throw new Exception("Failed to open generated private key.");

    // Create a CSR
    $options = array(
      'digest_alg'       => 'sha1',
      'private_key_bits' => 2048,
      'private_key_type' => OPENSSL_KEYTYPE_RSA,
      'encrypt_key'      => false,
    );
    $csr = openssl_csr_new($subject, $private_key, $options);
    if(!$csr)
      throw new Exception("Failed to create a CSR.");

    // Convert raw CA to OpenSSL instances
    $ca_pkey = null;
    $ca_cert = null;
    if($ca_pkey_raw && $ca_cert_raw){
      $ca_pkey = openssl_pkey_get_private(self::BuildPemKey($ca_pkey_raw, self::PEM_PRIVATE));
      if(!$ca_pkey)
        throw new Exception("Invalid CA private key.");

      $ca_cert = openssl_x509_read(self::BuildPemKey($ca_cert_raw, self::PEM_CERTIFICATE));
      if(!$ca_cert)
        throw new Exception("Invalid CA certificate.");
    }

    // Create a signed cert (by CA or self signed)
    if(!$ca_pkey)
      $ca_pkey = $private_key; // self sign
    $cert = openssl_csr_sign($csr, $ca_cert, $ca_pkey, 365, $options, $serial);
    if(!$cert){
      while ($msg = openssl_error_string())
        syslog(LOG_ERR, $msg);
      throw new Exception("Failed to create a cert.");
    }

    // Export the cert
    openssl_x509_export($cert, $cert_output);
    if(!$cert_output)
      throw new Exception("Failed to output a cert.");

    return crypt::cleanupPem($cert_output);
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
  const PEM_CERTIFICATE= "certificate";


  public static function BuildAuthorizedKey($private_key){
    $pubkey = self::ExtractPublicFromPrivateKey(self::cleanupPem($private_key));
    $key = self::pem2openssh($pubkey);
    return $key;
  }


    //fingerprint is the md5 of the base64 decoded public key (openssh format)
  public static function GetFingerPrint($public_key){
    list(,$key) = explode(' ', $public_key ,2);
    return md5(base64_decode($key));

  /**
        //initial implementation uses stdin && ssh-keygen
    $pubkey = self::BuildAuthorizedKey($private_key);

    //use stdin so we dont write pkey in command line
    $descriptorspec = array( array("pipe", "r"), array("pipe", "w"), array("pipe", "w") );
    $process = proc_open('( ssh-keygen -lf /dev/stdin <<< $(cat /dev/stdin) )', $descriptorspec, $pipes);
    if (!is_resource($process))
        throw new Exception("Invalid process");
    fwrite($pipes[0], $pubkey);
    fclose($pipes[0]);

    $contents = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $return_value = proc_close($process);
    if($return_value !== 0)
        throw new Exception("Invalid return value");
    $contents = explode(' ', trim($contents), 3);

    return array(
        'key_size'        => $contents[0],
        'key_fingerprint' => str_replace(':', '', $contents[1]),
        'key_comment'     => $contents[2],
    );
  */
  } 


  public static function pem_seal($public_key, $data){
    openssl_seal($data, $sealed, $env, array($public_key) );

    $body = crypt::pem_forge(array(
        array('type' => "MESSAGE",   'data' => $sealed),
        array('type' => "ENVELOPPE", 'data' => $env[0]),
    ));

    return $body;
  }

  public static function pem_open($private_key, $payload){
        // decrypt the data and store it in $open
      $blocks = array_column(self::pem_parse($payload), 'data', 'type');

      if (!openssl_open($blocks['MESSAGE'], $open, $blocks['ENVELOPPE'], $private_key))
        throw new Exception("Corrupted payload");

    return $open;
  }



  public static function cleanupPem($key){
    $key = preg_replace("#\r?\n#" , "", $key);
    $key = preg_reduce("#^-+BEGIN(?: RSA)? (?:PUBLIC|PRIVATE)?(?:CERTIFICATE| KEY)-+(.*?)-+END(?: RSA)? (?:PUBLIC|PRIVATE)?(?:CERTIFICATE| KEY)-+#m", $key);
    return $key;
  }

  public function pem_forge($blocks, $binary = true){
    $out = array();
    foreach($blocks as $block) {
        $out[] = sprintf("-----BEGIN %s-----", $block['type']);
        $out = array_merge($out, str_split( $binary ? base64_encode($block['data']) : $block['data'], 64));
        $out[] = sprintf("-----END %s-----", $block['type']);
    }
    return join("\n", $out)."\n";
  }

  public function pem_parse($file, $binary = true){
    $blocks = array(); $part = null; $block = null;
    foreach(explode("\n", $file) as $line) {
        if(preg_match('#^-{5}BEGIN (.*?)-{5}$#', $line, $out)) {
            $part = $out[1]; $block = "";
        } else if($part && $line == "-----END $part-----") {
            $blocks[] = array('type' => $part, 'data' => $binary ? base64_decode($block) : $block);
            $part = null; $block = null;
        } else if($part)
            $block .= $line;        
    }
    return $blocks;
  }


  public static function BuildPemKey($key_str, $type=crypt::PEM_PUBLIC) {
    $lines = str_split($key_str, 64);
    switch($type){
      case self::PEM_PRIVATE:
        array_unshift ($lines, "-----BEGIN RSA PRIVATE KEY-----");
        array_push    ($lines, "-----END RSA PRIVATE KEY-----");
        break;
      case self::PEM_PUBLIC:
        array_unshift ($lines, "-----BEGIN PUBLIC KEY-----");
        array_push    ($lines, "-----END PUBLIC KEY-----");
        break;
      case self::PEM_CERTIFICATE:
        array_unshift ($lines, "-----BEGIN CERTIFICATE-----");
        array_push    ($lines, "-----END CERTIFICATE-----");
        break;
      default:
        throw new Exception("Unsupported PEM type $type");
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
    $openssh_data = strip_start($openssh_data, self::SSH_RSA);
    $data = base64_decode($openssh_data);

    list(,$alg_len)  = unpack('N', substr($data, 0, 4));
    $alg = substr($data, 4, $alg_len);

    if ($alg !== self::SSH_RSA)
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

    $data  = pack("N", strlen(self::SSH_RSA)).self::SSH_RSA;
    $data .= pack("N", strlen($e['data'])).$e['data'];
    $data .= pack("N", strlen($n['data'])).$n['data'];

    return self::SSH_RSA." ".base64_encode($data);
  }


}
