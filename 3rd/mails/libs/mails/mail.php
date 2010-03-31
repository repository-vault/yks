<?php


class mail extends mime {


  function __construct($mail_name){

    $verif_mail = compact('mail_name');

    $mail_infos = sql::row("ks_mails_list", $verif_mail);

    if(! $mail_infos) 
        throw rbx::error("Unable to load email : $mail_name");

    $this->subject = preg_replace("#\{([a-z0-9_-]+)\}#i", "&$1;", $mail_infos['mail_title']);
    $this->from    = SITE_DOMAIN." <webmaster@".SITE_DOMAIN.">";

    if($cc = $mail_infos['mail_cc']) $this->cc($cc);

    $this->first_part = mails::load_children( $this, $mail_infos['mail_first_part'] );


  }

  function send($to=false){
    if($to) $this->to($to);
    $contents = $this->encode();
    return smtp_lite::smtpsend($contents, $this->dests);
  }

  function attach_file($file_path, $filename=null){

    if(!$this->first_part->is_composed())
        return false;


    return $this->first_part->add_file($file_path , $filename);
  }



}
