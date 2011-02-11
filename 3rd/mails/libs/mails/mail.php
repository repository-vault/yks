<?php


class mail extends mime {
  const inline_construct = "inline_construct";

  function __construct($from){
    $this->from    = SITE_DOMAIN." <webmaster@".SITE_DOMAIN.">";

    if($from == self::inline_construct)
        return;

    $verif_mail =  is_numeric($from)
        ? array('mail_id'   => (int)$from)
        : array('mail_name' => (string) $from);
    $mail_infos = sql::row("ks_mails_list", $verif_mail);
    $this->__construct_db($mail_infos);
  }

  public static function inline($subject, $body, $content_type = "text/plain"){
    $mail = new self(self::inline_construct);
    $mail->subject = $subject;
    $first_part = array(
        'content-type'  => $content_type,
        'part_id'       => 1,
        'part_contents' => $body,
    );
    $mail->first_part = new mime_part($mail, $first_part);
    return $mail;
  }

  private function __construct_db($mail_infos){
    if(! $mail_infos) 
        throw rbx::error("Unable to load email : $mail_name");

    $this->subject = preg_replace("#\{([a-z0-9_-]+)\}#i", "&$1;", $mail_infos['mail_title']);
    if($cc = $mail_infos['mail_cc']) $this->cc($cc);
    if($cci = $mail_infos['mail_cci']) $this->cci($cci);

    $this->first_part = mails::load_children( $this, $mail_infos['mail_first_part'] );
  }


  function send_mobile($to){

    $message = $this->first_part->encode(true);
    return mms::send_message($to, $message);
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
