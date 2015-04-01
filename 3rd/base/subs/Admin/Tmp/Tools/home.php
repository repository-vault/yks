<?php
if($action=="crypt"){
    rbx::ok(crypt($_POST['crypt_text'], $_POST['crypt_key']));

}