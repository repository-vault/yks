<?php

class sys_wol {
 function wake($mac, $broadcast = '255.255.255.255', $port = 7){
    $mac_hex = array_map('hexdec', explode('-', $mac));

    array_unshift($mac_hex, 'c*');
    $mac = call_user_func_array('pack', $mac_hex);

    $packet = str_repeat("\xFF", 6).str_repeat($mac, 16);

    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
    socket_sendto($sock, $packet, strlen($packet), 0, $broadcast, $port); 
    socket_close($sock);

    //$fp = fsockopen("udp://255.255.255.255", 7, $errno, $errstr);
    //fwrite($fp, $packet);
    //fclose($fp);
  }

}