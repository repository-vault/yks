<?

if($action=="shop_orders_expired_close")try{
    $expiration = _NOW - 3600*8;
    $data = array('order_status'=>'aborted');
    $verif_old = array(
        'order_status'=>'process',
        "order_start < $expiration"
    ); sql::update("ks_shop_orders", $data,  $verif_old);
    $current_liste->repage();
    rbx::ok("Les sessions ont été purgées");
}catch(rbx $e){}