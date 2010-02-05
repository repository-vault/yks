<?



sql::select("`ks_mails_list` LEFT JOIN `ks_mails_parts` ON mail_first_part = part_id",
    true,'*','ORDER BY mail_name');

$mails_list = sql::brute_fetch('mail_id');



if($action=="mail_delete")try{
    $mail_id = (int)$_POST['mail_id'];
    if(!$mails_list[$mail_id])
        throw rbx::error("Impossible de supprimer ce mail");


    $verif_mail = compact('mail_id');
    sql::delete("ks_mails_list", $verif_mail);
    return jsx::js_eval(JSX_RELOAD);

}catch(rbx $e){}