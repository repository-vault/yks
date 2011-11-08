<?

if($action == "sendmail") try {
    $dest = $_POST['send_to'];
    $res = $mail->send($dest);
    if($res)
        rbx::ok("Mail send to $dest");
    else rbx::error("Could not send mail !!");
    //rbx::ok($mail->trace());
} catch(rbx $e){}