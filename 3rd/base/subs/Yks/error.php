<?php

$error_code=(int)$sub0;

if(JSX){
  rbx::error("Impossible de trouver la page demandée");
  jsx::$rbx=true;
}

$error_infos = current(yks::$get->config->errors
    ->xpath("error[@code='$error_code'][@visibility='public']"));

$reloc_url = $_SESSION[SESS_TRACK_ERR];

