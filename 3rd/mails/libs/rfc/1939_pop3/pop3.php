<?php

include_once "822.php";

class pop3 {
    //state = ready (non connected)
    //state = end ( connnection close)
    const OK ='+OK';
    const CONTENTS_TERMINAISON="\r\n.\r\n";
    const BLANK_LINE=CRLF;
    const LWSP='[\s]';
    static $trace=false;

    private $state;
    private $config=array(
        'authentification_mode'=>'login',
    );
    private $lnk=false;
    private $maildrop_infos=array('messages_list'=>array());
    private $apop_greeting=array();
    private $last_error='';

    
    static function parse_headers($headers){
        $head=preg_replace('#'.CRLF.self::LWSP.'+#',' ',$headers); $message_headers=array();

        preg_match_all("#(.*?):\s*(.*)#",$head,$out,PREG_SET_ORDER);
        foreach($out as $data){
            $data[1]=ucfirst(preg_replace("#(-[a-z])#e",'strtoupper("$1")',$data[1]));
            if($data[1]=="Content-Type"){ //loading extras
                list($value,$params) = explode(';',$data[2],2);
                $data=array($data[1]=>$value,$data[1]."-Details"=>rfc822::header_extras(";$params"));
            } else $data=array($data[1]=>rfc822::header_decode($data[2]));
            $message_headers = array_merge_recursive($message_headers,$data);
        }
        if($tmp=rfc822::date_valid($message_headers['Date']))$message_headers['Date']=$tmp;

        return $message_headers;
    }

    function __construct($config){
        $this->state="ready";
        $this->config['in']=$config['server']['in'];
        if($config['options'])
            $this->config=array_merge($this->config,$config['options']);
        
    }

    function abort($msg){
die($msg);
        if($this->lnk) fclose($this->lnk);
        $this->state="end";
        throw rbx::error($msg);
    }

    function close($gracefull=true){
        if(!$gracefull){
            $this->send("QUIT");
            $test=$this->read();
            if(!$test['valid']) $this->abort("Unable to quit é_è");
        } fclose($this->lnk); $this->lnk=false; $this->state="end";
        return true;
    }

    function send($msg,&$err=null){
        if(self::$trace) rbx::ok("<< ".trim($msg));
        fwrite($this->lnk,$msg.CRLF);
        $test=$this->read();
        if($test['valid']) return $test['message']?$test['message']:true;
        else {
            $this->last_error=$err=$test['message'];
            return false;
        }
        die("here");
    }

    function read(){
        $tmp=fread($this->lnk,1024);
        if(self::$trace) rbx::ok(">> ".trim($tmp));
        $message=explode(" ",$tmp,2);
        return array(
            'valid'=>$message[0]==self::OK,
            'message'=>$message[1]
        );
    }

    function retrieve_maildrop_stats(){
        $response=$this->send("STAT")
             or $this->abort("Unable to retrieve maildrop informations");

        $data=explode(' ',$response);
        $data=array(
            'maildrop_messages_nb' => $data[0],
            'maildrop_size' => $data[1],
        ); $this->maildrop_infos['maildrop_stats']=$data;
        return $data;
    }


    function filter_line($where){
        $str='';while(($l=fgets($this->lnk,1024)) !=$where) $str.=$l;
        return $str;
    }

    function filter_contents($where){
        stream_set_blocking($this->lnk,false);
        $str='';while(($i=strpos($str,$where))===false) $str.=fread($this->lnk,1024);
        stream_set_blocking($this->lnk,true);
        return substr($str,0,$i);
    }

    function retrieve_headers($message_uidl,$lines=2){
        $message_nb=$this->maildrop_infos['messages_list'][$message_uidl]['message_nb'];
        $response=$this->send("TOP $message_nb $lines ");

        if(!$response) return array();

        $headers=$this->filter_line(self::BLANK_LINE);
        $message_headers=self::parse_headers($headers);

        $message_top=$this->filter_contents(self::CONTENTS_TERMINAISON);
        return compact('message_headers','message_top');
    }

    function retrieve_message($message_uidl){
        $message_nb=$this->maildrop_infos['messages_list'][$message_uidl]['message_nb'];
        $response=$this->send("RETR $message_nb");
        if(!$response) return false;
        $headers=$this->filter_line(self::BLANK_LINE);
        $message_headers=self::parse_headers($headers);

        $message_contents=$this->filter_contents(self::CONTENTS_TERMINAISON);
        return compact('message_headers','message_contents');

    }


    function retrieve_maildrop_infos($message_nb=false){
        $query="UIDL ".($message_nb?$message_nb:'');
        $message_infos=$this->send($query)
             or $this->abort("Unable to retrieve maildrop infos");
        if($message_nb)$contents=$message_infos;
        else $contents=$this->filter_contents(self::CONTENTS_TERMINAISON).CRLF;

        $find=preg_match_all("#([0-9]+) (.*(?:,S=([0-9]+))?)[^\x21-\x7E]#U",$contents,$out);
        if(!$find) return array();
        list(,$messages_nb,$messages_uidl,$messages_size)=$out;$message_source=false;
        if($message_nb && ($message_index=array_search($message_nb,$messages_nb))!==false)
            $message_source=$messages_uidl[$message_index];

            //si on a pas pu retrouver la taille
        if(!array_filter($messages_size)){
            $query="LIST ".($message_nb?$message_nb:'');
            $this->send($query)
                 or $this->abort("Unable to retrieve maildrop infos");
            $contents=$this->filter_contents(self::CONTENTS_TERMINAISON);
            $find=preg_match_all("#(\d+) (\d+)#",$contents,$out);
            if($find && $out[1]==$messages_nb) $messages_size=$out[2];
        }
        $messages_list=array();
        foreach($messages_uidl as $k=>$message_uidl){
            $messages_list[$message_uidl]=array(
                'message_uidl'=>$message_uidl,
                'message_nb'=>$messages_nb[$k],
                'message_size'=>$messages_size[$k],
            );
        }

        $this->maildrop_infos['messages_list']=array_merge(
            $this->maildrop_infos['messages_list'],
            $messages_list
        );
        return $message_source?$messages_list[$message_source]:$messages_list;
    }



    function reset(){
        $this->close();
        $this->connect();
    }
    
    function connect(){
        $config_in=$this->config['in'];
        $this->lnk=fsockopen($config_in['server_host'],$config_in['server_port'])
            or $this->abort("Unable to open connection with {$config_in['server_host']}:{$config_in['server_port']}");

        $this->state="AUTHORIZATION";

        $response=$this->read();
        if(!$response['valid']) $this->abort("Server explicitly denied connection");

        if(preg_match("#<.*?>#",$response['message'],$out))
              $this->apop_greeting=$out[0];


        if($this->config['authentification_mode']=='login')
            $this->authenticate();
 

    }


        //ping the server for new mail
    function apop(){
        $digest=($this->apop_greeting.$this->config['in']['account_pswd']);
        $hash=md5($digest);
        $this->send("APOP {$this->config['in']['account_login']} $hash");
        $response=$this->read();
        if(!$response['valid']) $this->abort("Password invalid");

        $this->state="TRANSACTION";
        return true;
    }

    function authenticate(){
        $this->send("USER {$this->config['in']['account_login']}")
             or $this->abort("You are not allowed to connect");

        $this->send("PASS {$this->config['in']['account_pswd']}")
             or $this->abort("Password invalid");
        $this->state="TRANSACTION";
        return true;
    }


}