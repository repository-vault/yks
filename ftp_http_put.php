<?
//http://doc.exyks.org/wiki/Source:ext/http/ftp_http_put

class ftp_http_put {

  const HTTP_CLIENT_FTP_HEADER = "X-HTTP-Put";
  const HTTP_SERVER_FTP_HEADER = "HTTP_X_HTTP_PUT";

  const HTTP_CLIENT_FTP_SIGN = "X-HTTP-Put-Sign";
  const HTTP_SERVER_FTP_SIGN = "HTTP_X_HTTP_PUT_SIGN";

  public static $secret;


  static function send($file_path, $ftp_remote, $ftp_put_gw) {
    if(!self::$secret)
        throw new Exception("Specify secret first !!");
        


        //ftp_remote is ftp://user:passwd@host/remote_path
    $ftp = parse_url($ftp_remote);
        //gw is http://host/gw
    $gw  = parse_url($ftp_put_gw);

    if($gw['scheme'] != 'http')
        throw new Exception("Only through http ..");
    $gw['port'] = $gw['port'] ? $gw['port'] : 80;

    $fp = fsockopen($gw['host'], $gw['port']);

    $file_size = filesize($file_path);
    $file      = fopen($file_path, "r");

    $sign = hash_hmac("sha1", $ftp_remote, self::$secret);

    $CRLF = "\r\n";
    $query_head = "PUT {$gw['path']} HTTP/1.0".$CRLF
        ."Host: {$gw['host']}".$CRLF
        ."Content-Length: $file_size".$CRLF
        .self::HTTP_CLIENT_FTP_HEADER.": $ftp_remote".$CRLF
        .self::HTTP_CLIENT_FTP_SIGN.": $sign".$CRLF
         .$CRLF;

    $res=  fwrite($fp, $query_head);
    stream_copy_to_stream($file, $fp);

    $res = stream_get_contents($fp);
    print_r($res);

  }

  static function receive(){
    if(!self::$secret)
        throw new Exception("Specify secret first !!");

    $ftp_remote = $_SERVER[self::HTTP_SERVER_FTP_HEADER];
    $sign       = $_SERVER[self::HTTP_SERVER_FTP_SIGN];
    $challenge = hash_hmac("sha1", $ftp_remote, self::$secret);
    if($challenge != $sign)
        throw new Exception("Invalid hash challenge");

    $ftp  = parse_url($ftp_remote);
    if($ftp['scheme'] != 'ftp')
        throw new Exception("Invalid $scheme");


    $test = `which ncftpput`;
    if(!$test)
        throw new Exception("Could not find ncftpput");

    $ftp_lnk = popen("ncftpput -u {$ftp['user']} -p {$ftp['pass']} -c {$ftp['host']} {$ftp['path']}", "w");

    $input_lnk = fopen("php://input", "r");
    stream_copy_to_stream($input_lnk, $ftp_lnk);
  }

}
