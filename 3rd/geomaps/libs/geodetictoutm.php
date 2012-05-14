<?php

function geodetictoutm($lat, $lon) {

    // Ellipsoid model constants (actual values here are for WGS84)

    $sm_a = 6378137.0;
    $sm_b = 6356752.314;
    $sm_EccSquared = 0.000669437999013;
    $UTMScaleFactor = 0.9996;

    // get UTM XY from Lat Lon
    $zone = floor(($lon + 180.0) / 6) + 1;

    // Compute the UTM zone
    $phi = $lat / 180 * Pi();
    $lambda = $lon / 180 * Pi();
    $lambda0 =  (-183 + ($zone * 6))/ 180 * Pi(); //center merdian

    // Precalculate ep2
    $ep2 = (pow($sm_a,2) - pow($sm_b,2) ) / pow($sm_b,2);

    // Precalculate nu2
    $nu2 = $ep2 * pow(cos($phi),2);

    // Precalculate N
    $N = pow($sm_a,2) / ($sm_b * sqrt(1 + $nu2));

    // Precalculate t
    $t = tan ($phi);
    $t2 = $t * $t;
    $tmp = pow($t2,3) - pow($t,6);

    // Precalculate l
    $l = $lambda - $lambda0;

    //Precalculate coefficients for l**n in the equations below
    //so a normal human being can read the expressions for easting and northing

    $l3coef = 1 - $t2 + $nu2;
    $l4coef = 5 - $t2 + 9 * $nu2 + 4 * ($nu2 * $nu2);
    $l5coef = 5 - 18 * $t2 + ($t2 * $t2) + 14 * $nu2 - 58 * $t2 * $nu2;
    $l6coef = 61 - 58 * $t2 + ($t2 * $t2) + 270 * $nu2 - 330 * $t2 * $nu2;
    $l7coef = 61 - 479 * $t2 + 179 * ($t2 * $t2) - ($t2 * $t2 * $t2);
    $l8coef = 1385 - 3111 * $t2 + 543 * ($t2 * $t2) - ($t2 * $t2 * $t2);

    // ArcLengthOfMeridian
    //Precalculate n
    $n2 = ($sm_a - $sm_b) / ($sm_a + $sm_b);
    //Precalculate alpha
    $alpha = (($sm_a + $sm_b) / 2) * (1 + (pow($n2,2) / 4) + (pow($n2,4) / 64));
    //Precalculate beta
    $beta = (-3 * $n2 / 2) + (9 * pow($n2,3) / 16) + (-3 * pow($n2,5) / 32);
    //Precalculate gamma
    $gamma = (15 * pow($n2,2) / 16) + (-15 * pow($n2,4) / 32);
    //Precalculate delta
    $delta = (-35 * pow($n2,3) / 48) + (105 * pow($n2,5) / 256);
    //Precalculate epsilon
    $epsilon = (315 * pow($n2,4) / 512);
    //Now calculate the sum of the series and return
    $ArcLengthOfMeridian = $alpha * ($phi + ($beta * sin(2 * $phi)) + ($gamma * sin(4 * $phi)) + ($delta * sin (6 * $phi)) + ($epsilon * sin(8 * $phi)));

    //Calculate easting(x)
    $x = $N * cos($phi) * $l
      + ($N / 6 * pow(cos($phi),3) * $l3coef * pow($l,3))
        + ($N / 120 * pow(cos($phi),5) * $l5coef * pow($l,5))
        + ($N / 5040 * pow(cos($phi),7) * $l7coef * pow($l,7));

    //Calculate northing(y)
    $y = $ArcLengthOfMeridian
      + ($t / 2 * $N * pow(cos($phi),2) * pow($l,2))
        + ($t / 24 * $N * pow(cos($phi),4) * $l4coef * pow($l,4))
        + ($t / 720 * $N* pow(cos($phi),6) * $l6coef * pow($l,6))
        + ($t / 40320 * $N * pow(cos($phi),8) * $l8coef * pow($l,8));


    $x = $x * $UTMScaleFactor + 500000;
    $y = $y * $UTMScaleFactor;

    //print_r(get_defined_vars ( ));die;
    return array($x, $y, $zone);

}