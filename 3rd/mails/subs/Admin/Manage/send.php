<?

if($action == "sendmail") try {
    $dest = $_POST['send_to'];
    $mail->send($dest);
    rbx::ok($mail->trace());
} catch(rbx $e){}