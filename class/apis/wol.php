<?

class sys_wol {
 function wake($mac){
    $mac_hex = array_map('hexdec', explode('-', $mac));

    array_unshift($mac_hex, 'c*');
    $mac = call_user_func_array('pack', $mac_hex);
    $fp = fsockopen("udp://255.255.255.255", 7, $errno, $errstr);
    $packet = str_repeat("\xFF", 6).str_repeat($mac, 16);
    //print_r(unpack("C*", $packet));die;
    fwrite($fp, $packet);
    fclose($fp);
  }

}