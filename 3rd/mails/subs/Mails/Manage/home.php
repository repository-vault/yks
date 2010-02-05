<?

if($mail_id )  {
    $verif_mail = compact('mail_id');
    $mail_infos = sql::row("ks_mails_list", $verif_mail);
    $mail_first_part  = $mail_infos['mail_first_part'];
    if($mail_first_part)
        $mail_infos['first_part_infos'] = sql::row("ks_mails_parts",array('part_id'=>$mail_first_part));
}

if($action=="mail_manage")try {
    $data=array(
        'mail_name'=>$_POST['mail_name'],
        'mail_title'=>$_POST['mail_title'],
        'mail_descr'=>$_POST['mail_descr'],
        'mail_cc'=>$_POST['mail_cc'],
    );
    if(!$data['mail_name'])
        throw rbx::warn("Veuillez specifier un nom pour le modèle de mail","mail_name");
    if(!$data['mail_title'])
        throw rbx::warn("Veuillez specifier un titre pour le mail","mail_title");


    if(!$mail_first_part){
        $part_data = array(
            'content-type'=>$_POST['mime_content_type']
        );
        $mail_first_part = sql::insert("ks_mails_parts", $part_data,true);

        if(!$mail_first_part) 
            throw rbx::error("Le type de contenu du mail est invalide");

    } $data['mail_first_part'] = $mail_first_part;

    if(!$mail_id) $res = $mail_id = sql::insert("ks_mails_list", $data, true);
    else $res = sql::update("ks_mails_list", $data, $verif_mail);

    if($res) reloc("/?$href_fold//$mail_id");
    else throw rbx::error("Une erreur est survenue lors de l'enregistrement");

}catch(rbx $e){}

