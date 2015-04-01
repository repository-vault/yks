<?php

$addr_id=(int)$sub0;

$verif_addr=compact('addr_id');

$addr_infos=sql::row("ks_users_addrs",$verif_addr);
