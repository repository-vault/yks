<?php


if($action=="contact_us")try {
    $data=array(
        'message_title'=>$_POST['message_title'],
        'message_contents'=>$_POST['message_contents'],
    );
    if(!$data['message_title'])
        throw rbx::error("Please specify a subject");
    if(!$data['message_contents'])
        throw rbx::error("Please specify some contents");





}catch(rbx $e){}