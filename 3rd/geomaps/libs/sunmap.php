<?php
/*
  Freely inspired from J.P.Westerhof http://www.edesign.nl/examples/sunlightmap/
*/

class sunmap {
  const EARTH_ANGLE = 23.5;
  private $daytime_image_path;
  private $nighttime_image_path;
  private $img_day;
  private $img_nig;
  private $show_sun_reflection = false;

  private $time;

  function __construct($daytime_image, $nighttime_image) {
    $this->daytime_image_path    = $daytime_image;
    $this->nighttime_image_path  = $nighttime_image;

    $this->set_time(time());

    $this->prepare();
  }

  function set_time($time){
    $this->time = $time;
  }

  function prepare(){
    $this->img_day = imgs::imagecreatefromfile($this->daytime_image_path);
    $this->img_nig = imgs::imagecreatefromfile($this->nighttime_image_path);
  }

  function output($file = null){
    imagepng($this->render(), $file);
  }

  function render(){

    $img = imgs::imagecreatefromfile($this->daytime_image_path);

    list($w, $h) = array(imagesx($this->img_day), imagesy($this->img_day));

    $daysInYear = 365 + idate('L', $this->time);
    $dayOfYear = idate('z', $this->time);

    $time = idate('H', $this->time)
        + (idate('i', $this->time) / 60)
        + (idate('s', $this->time) / 3600);

    $timeZoneOffset = idate('Z', $this->time) / 3600;

    $time = (24 + $time + 6 - $timeZoneOffset - $mapoffset) ;
    while($time > 24) $time -= 24; //%!
    $time /= 24;


    $vEarthToSun = new vector3d(
        sin((2*M_PI) * $time),
        0,
        cos((2*M_PI) * $time)
    );

    $year_ratio = ($dayOfYear - 173)/ $daysInYear;
    $tilt = (M_PI*2*self::EARTH_ANGLE/360)  * cos(2 * M_PI * $year_ratio );

    $seasonOffset = new vector3d(0, tan($tilt), 0);


    $vEarthToSun = $vEarthToSun->add($seasonOffset);

    $vEarthToSun->normalize();

    for($x = 0; $x < $w; $x++) for($y = 0; $y < $h; $y++) {
        $lat = (($y / ($h * 2)) - 1)*(2*M_PI);
        $lon = ($x/$w)*(2*M_PI);

        $earthNormal = new vector3d(
          sin($lat) * cos($lon),
          cos($lat),
          sin($lat) * sin($lon)
        ); $earthNormal->normalize();


        $surface_angle = $vEarthToSun->dot($earthNormal);

        if($surface_angle <= -0.1) { 
            // inside
            imagesetpixel($img, $x, $y, imagecolorat($this->img_nig, $x, $y)); //night
        } elseif($surface_angle < 0.1) {
            // very flat angle
            $ratio = ($surface_angle + 0.1) * 5;

            $colors = array(
                array(imagecolorat($this->img_day, $x, $y), $ratio),
                array(imagecolorat($this->img_nig, $x, $y), 1 - $ratio)
            );

            imagesetpixel($img, $x, $y, imgs::colorsblend($colors));
        } elseif($this->show_sun_reflection && $surface_angle > 0.97) {
            // almost aligned with the normal, sun reflection
            $day  = imagecolorat($this->img_day, $x, $y);

            $ratio = (1 - $surface_angle) * 30;
            $colors = array(
                array($day, $ratio * 15),
                array(imgs::COLOR_WHITE, 1 - $ratio)
            );
            imagesetpixel($img, $x, $y, imgs::colorsblend($colors));
        }
    }

    return $img;
  }

}
