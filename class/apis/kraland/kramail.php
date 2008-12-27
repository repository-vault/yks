<?


include "$class_path/mails/mails.php";
include "$class_path/mails/mail_base.php";
include "$class_path/apis/kraland/kramail_part.php";



class kramail extends mail_base {

  function __construct($mail_name){

    $verif_mail = compact('mail_name');
    $mail_infos = sql::row("ks_mails_list", $verif_mail);

    if(! $mail_infos) 
        throw rbx::error("Unable to load email : $mail_name");

    $this->subject = preg_replace("#\{([a-z0-9_-]+)\}#i", "&$1;", $mail_infos['mail_title']);
    $this->first_part = mails::load_children( $this, $mail_infos['mail_first_part'],'kramail_part' );

  }


  function output_headers(){
    $this->subject = jsx::translate($this->subject);
  }


  function send($to=false){
    include_once "yks/class/socks/socks.php";

    $this->to = $to;
    $contents = $this->encode();

    $kramail=new sock_lnk(KRA_URL);

    $cookie_kmail=attributes_to_assoc(yks::$get->config->apis->kra->cookies_kmail);
    $kramail->set_cookies($cookie_kmail);

    $data=array(
        "action"=>"km_post",
        "page"=>"1;4;3;122554;0",
        "p1"=>utf8_decode($this->to),
        "p2"=>utf8_decode($this->subject),
        "p3"=>$cookie_kmail['citoyen_id'],
        "p4"=>1,
        "p5"=>$ar,
        "p7"=>0,
        "message"=>utf8_decode($contents),
    );
    $headers=array("method"=>"POST",'data'=>$data);
    $kramail->request("/main.php",$headers);
    $kramail->close();
    return true;
  }

}

