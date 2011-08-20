<?php


function detect_edge_coords($img){

  list($img_w , $img_h) = array(imagesx($img), imagesy($img));

  $map = array(); $grid = array();
  for($x=0; $x<=$img_w;$x++) {
    for($y=0; $y<=$img_h;$y++) {
      $on = ($x == $img_w || $y == $img_h) ? false : ! ((bool)imagecolorat($img, $x, $y)) ;

      $map[$y][$x] = $on;
      $uon = $map[$y-1][$x]; $xuon = $on ^ $uon;
      $lon = $map[$y][$x-1]; $xlon = $on ^ $lon;



        //col
      if( $xlon) {
         if( is_null($sy = $grid['y'][$x]['last'][$y]) )
            $sy = $y;
        $line = array($x, $sy, $x, $y +1, array( $on ? -1 : 1, 0) ); //coords + normal
        $grid['y'][$x]['last'][$y+1] =  $sy;
        $grid['lines'][ "col". ($x + $sy * $img_w ) ] = $line;
      }

        //row
      if( $xuon) {
         if( is_null($sx = $grid['x'][$y]['last'][$x]) )
            $sx = $x;
        $line = array($sx, $y, $x +1, $y, array( 0, $on ? -1 : 1));
        $grid['x'][$y]['last'][$x+1] =  $sx;
        $grid['lines'][ "row" .($sx + $y * $img_w ) ] = $line;
      }

    }
  }

  return $grid['lines'];
}




function detect_zones($img, $color){
    //header(TYPE_PNG);imagepng($img);die;

    list($img_w, $img_h) = array(imagesx($img), imagesy($img));
    $zones = array();
    $ease = 30; $zone_id = 0;

    for( $y=0; $y<$img_h;$y++) for($x=0; $x<$img_w; $x++){
        $path = $y * $img_w + $x;

        if(isset($zones[$path])) continue;

        $to = imagecolorat($img, $x, $y);

        if(abs($to-$color)>$ease) {
            $zones[$path] = false;
            continue;
        }

        $zone_id++;
        $todo = array($path => array($x, $y));


        while(list($id, $pos) = each($todo)) {
            if(isset($zones[$id])) continue;
            list($mx, $my) = $pos;
            $to = imagecolorat($img, $mx, $my);
            if(abs($to-$color)>$ease)continue;
            $zones[$id] = $zone_id;
            extend_square(&$todo, $zones, $mx, $my, $img_w, $img_h);

        }

    }


    return array($zones_dims, $zones); //use as tupple
}


function nine_square($x, $y, $w, $h){
    $ret  = array();

    if($y>0) {
        $ret[($x+($y-1)*$w)       ]= array($x  , $y-1);
        if($x>0)
          $ret[(($x-1) + ($y-1)*$w) ]= array($x-1, $y-1);

        if($x<$w-1)
          $ret[(($x+1)+($y-1)*$w)   ]=  array($x+1, $y-1);
    } 
    if($y<$h-1) {

        $ret[($x+ ($y+1)*$w)     ]=  array($x  , $y+1);

        if($x>0)
          $ret[(($x-1)+($y+1)*$w)    ]= array($x-1, $y+1);

        if($x<$w-1)
          $ret[(($x+1)+ ($y+1)*$w)  ]=  array($x+1, $y+1);
    } 

    if($x>0)
      $ret[(($x-1)+$y*$w)        ]=array($x-1, $y  );

    if($x<$w-1)
      $ret[(($x+1)+$y*$w)        ]= array($x+1, $y  );

    return $ret;
}


function extend_square(&$todo, $zones, $x, $y, $w, $h){
    $tmp = nine_square($x, $y, $w, $h);

    foreach($tmp as $id=>$coord)
        if(!isset($todo[$id]) && !isset($zones[$id]))
            $todo[$id] = $coord;
}

function degrad($from, $to, $step){
    return imgs::colorget(array(
        'red'=>($to['red']-$from['red'])*$step+$from['red'],
        'green'=>($to['green']-$from['green'])*$step+$from['green'],
        'blue'=>($to['blue']-$from['blue'])*$step+$from['blue'],
    ));
}
