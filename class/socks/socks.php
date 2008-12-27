<?
define('CRLF',"\r\n");
include "http.php";


class sock_lnk {
  private $sock;
  public $cookies;
  private $host;
  private $port;
  private $enctype;
  public $debug=false;
  public $headers;


  function __construct($website){
    $infos=parse_url($website);
    $this->host=$infos['host'];
    $ssl=$infos['scheme']=='https';
    $this->port=$ssl?443:80;
    $this->enctype=$ssl?'ssl://':'';
    $this->cookies=array();
  }


  function request($uri,$headers=array()){

    if(!$this->sock)$this->connect();

    set_time_limit(3); stream_set_timeout($this->sock,3);

    $method=$headers['method']?$headers['method']:'GET';$post=$method=='POST';
    $options=(array)$headers['options'];
    if($post) $datas=http_build_query($headers['data'],null,'&');

    $commons_h=array(
        'Host'=>$this->host,
        'Connection'=>'keep-alive',
        'Keep-Alive'=>300,
        'Referer'=>'',
        'User-Agent'=> 'Mozilla/5.0 (Windows; U; Windows NT 5.1; fr; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3',
        'Cookie'=>$this->cookies?urldecode(http_build_query($this->cookies,null,'; ')):false,
        'Content-Type'=>$post?'application/x-www-form-urlencoded':false,
        'Content-Length'=>$post?strlen($datas):false,
    );$headers=array_intersect_key(array_filter(array_merge($commons_h,$headers)),$commons_h);


    $this->query="$method $uri HTTP/1.1".CRLF;
    $this->query.=mask_join('', $headers, '%2$s: %1$s'.CRLF);
    $this->query.=CRLF;
    if($post) $this->query.= $datas;
    fputs($this->sock,$this->query);

    if(0)echo CRLF.CRLF.CRLF;
    if(0)echo preg_areplace(array("#^|(\r?\n(?=.))#" => '$1<<<< '), $this->query).CRLF;

    $try=0;do { $head="";
        while(($tmp=fgets($this->sock))!="\r\n")$head.=$tmp;
        preg_match("#HTTP/... ([0-9]{3}) #",$head,$out);$code=(int)$out[1];
    }while((!$code) && ($try++<10));$this->head=$head;$this->response=$code;

    if(0)echo preg_areplace(array("#^|(\r?\n(?=.))#" => '$1>>>> '), $this->head).CRLF;


    $this->headers = http::parse_headers($this->head);
    if($cookies = $this->headers['Set-Cookie']) foreach($cookies as $cookie)
        $this->cookie_manage( cookie::parse_header($cookie) );

    if(in_array('no_redirect',$options)) {
        $this->end_headers();
        return;
    }

    if($this->response==302){
        $this->end_headers();
        $location = (string) $this->headers['Location'];
        $location_infos=array_merge(
            array('host'=>$this->host),
            parse_url($location)
        );  //fuck tlds http://wiki.mozilla.org/TLD_List

        if($location_infos['host'] != $this->host){
            if(!preg_match($options['follow_to'], $location_infos['host']) ) return false;
            $this->close();$this->__construct($location);
            if($jar = $options['jar'][$location_infos['host']])  $this->cookies = $jar;
        }
        $location = $location_infos['path'].'?'.$location_infos['query'];
        $this->request($location);
    }

  }

  function cookie_manage($cookie){
    $this->cookies[$cookie->name] = $cookie->value;
  }

  function receive($filter=false,&$ret=NULL){
    $bfilter=(bool)$filter;
    if(!$this->sock)return false;
    $file_size = (int)$this->headers['Content-Length']->value;
    $chunked = (string) $this->headers['Transfer-Encoding'] == "chunked";

    $body='';
    set_time_limit(8); 
    if($file_size){
        stream_set_blocking($this->sock, 0);
        while(strlen($body)<$file_size-1 ){
            if(!$tmp=fgets($this->sock,1024)) continue; $body.=$tmp;
            if($bfilter && preg_match($filter,$body,$ret))return true;
            if(strlen($body)>=$file_size-1) return $body;
        } $this->end_headers(); return $body;
    } elseif($chunked){
        stream_set_blocking($this->sock,1);
         do {
            $tmp=fgets($this->sock);
            $chunk=substr($tmp,0,strspn($tmp,"abcdef0123456789"));
            $chunk_size=hexdec($chunk);$file_size+=$chunk_size;$tmp='';
            while(strlen($tmp)<$chunk_size && !feof($this->sock))
                $tmp.=fgets($this->sock);
            $body.=substr($tmp,0,$chunk_size);
            if($bfilter && preg_match($filter,$body,$ret)){$this->end_headers();return true;}
        }while($chunk!=="0");$this->end_headers();return $body;
    }
  }

  function save($file){ 
	$contents=$this->receive(); $charset="UTF-8";
	if(preg_match("#charset=([a-z0-9-]+)#i",$this->headers['Content-Type'],$out))
		$charset=$out[1];
	if($charset!="UTF-8") $contents=mb_convert_encoding($contents,"UTF-8",$charset);
	file_put_contents($file,$contents);
  }
  function save_ext_sess(){ $_SESSION["sock_lnk_{$this->host}"]['cookies']=$this->cookies; }
  function load_ext_sess(){ $this->cookies=$_SESSION["sock_lnk_{$this->host}"]['cookies']; }

  function connect(){
	$this->sock=fsockopen($this->enctype.$this->host,$this->port);
	if(!$this->sock) die("Unable to connect $this->host:$this->port");
  }
  function close(){ fclose($this->sock);$this->sock=null; }

  function set_cookies($cookies) { $this->cookies=$cookies;  }
  function end_headers(){
    if($this->headers['Connection']=='close')$this->close();
    elseif($file_size = $this->headers['Content-Length']->value) {
        stream_set_blocking($this->sock, 0); $body="";
        while(strlen($body)<$file_size-1 ) $body.=fgets($this->sock,1024);
    }
  }
}


function serial_post($array,$pref=''){
	$re=array();
	foreach($array as $key=>$value){
		$preff=$pref?"{$pref}[{$key}]":$key;
		if(!is_array($value))$re[]=$preff.'='.urlencode($value);
		else $re=array_merge($re,serial_post($value,$preff));
	}
	return $re;
}

function explode_url($url){
	$data=parse_url($url);
	parse_str($data['query'],$data['data']);
	return $data;
}

