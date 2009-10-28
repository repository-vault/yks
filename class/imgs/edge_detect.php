<?php


function detect_zones($img, $color){
    //header(TYPE_PNG);imagepng($img);die;

    $w = imagesx($img); $h = imagesy($img);
    $zones = array();
    $ease = 30; $zone_id = 0;

    for($y=$path=0; $y<$h;$y++) for($x=0;$x<$w;$x++,$path++){

        if(isset($zones[$path]))continue;

        $to=gray(imagecolorat($img,$x,$y));

        if(abs($to-$color)>$ease) {
            $zones[$path] = false;
            continue;
        }
        $zone_id++;
        $todo = array($path=>array($x,$y));
        $done=array($path);

        while(list($id,$pos) = each($todo)) {
            if(isset($zones[$id])) continue;
            list($mx, $my) = $pos;
            $to=gray(imagecolorat($img, $mx, $my));
            if(abs($to-$color)>$ease)continue;
            $zones[$done[] = $id] = $zone_id;
            extend_square($todo, $zones, $mx, $my, $w, $h);
        }
    
        if(count($done)<4) {
            $zone_id--;
            foreach($done as $id) $zones[$id] = false;
        }
    }

    $zones_dims = array();
    $zones_nb = $zone_id; $zone_start = array('xl'=>$w,'xr'=>0);
    for($y =$path= 0; $y<$h;$y++) for($x=0;$x<$w;$x++,$path++){
       if(!$zone_id=$zones[$path]) continue;
       if(!isset($zones_dims[$zone_id]))$zones_dims[$zone_id]=$zone_start;
       if($zones_dims[$zone_id]['xl']>$x)$zones_dims[$zone_id]['xl']=$x;
       if($zones_dims[$zone_id]['xr']<$x)$zones_dims[$zone_id]['xr']=$x;
    }

    return array($zones_dims, $zones); //use as tupple
}


function nine_square($x, $y, $w, $h){
    return array(
        (($x-1) + ($y-1)*$w) => array($x-1, $y-1),
        ($x+($y-1)*w)        => array($x  , $y-1),
        (($x+1)+($y-1)*$w)   => array($x+1, $y-1),
        (($x-1)+$y*$w)       => array($x-1, $y  ),
        (($x+1)+$y*$w)       => array($x+1, $y  ),
        (($x-1)+($y+1)*$w)   => array($x-1, $y+1),
        ($x+ ($y+1)*$w)      => array($x  , $y+1),
        (($x+1)+ ($y+1)*$w)  => array($x+1, $y+1),
    );
}


function extend_square(&$todo,$zones, $x, $y, $w, $h){
    $tmp = nine_square($x, $y, $w, $h);
    foreach($tmp as $id=>$coord)
        if(!isset($todo[$id]) && !isset($zones[$id]))
            $todo[$id] = $coord;
}

function degrad($from, $to, $step){
    return colorget(array(
        'red'=>($to['red']-$from['red'])*$step+$from['red'],
        'green'=>($to['green']-$from['green'])*$step+$from['green'],
        'blue'=>($to['blue']-$from['blue'])*$step+$from['blue'],
    ));
}
