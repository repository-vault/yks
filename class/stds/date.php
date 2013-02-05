<?php

class date {

  static function validate($date, $format=DATE_MASK, $zero_time = false){
    $format=preg_replace("#[a-z]#i","%$0",strtr($format,array('i'=>'M','s'=>'S')));

    if(!($tm=self::strptime($date,$format)))
        return false;
    $tm['tm_mon']+=1;

    $date = gmmktime($tm['tm_hour'],$tm['tm_min'],$tm['tm_sec'],
        $tm['tm_mon'], pick($tm['tm_mday'],1), 1900+pick($tm['tm_year'], 70));

    if(!$zero_time && $format != DATE_MASK)
        $date -= exyks::retrieve("USER_TZ");

    return $date;
  }

  static function strptime($date, $format) {
    $masks = array(
      '%d' => '(?P<d>[0-9]{2})',
      '%m' => '(?P<m>[0-9]{2})',
      '%Y' => '(?P<Y>[0-9]{4})',
      '%H' => '(?P<H>[0-9]{2})',
      '%M' => '(?P<M>[0-9]{2})',
      '%S' => '(?P<S>[0-9]{2})',
    );
    $rexep = "#".strtr(preg_quote($format), $masks)."#";
    if(!preg_match($rexep, $date, $out))
      return false;

    $ret = array(
      "tm_sec"  => (int) $out['S'],
      "tm_min"  => (int) $out['M'],
      "tm_hour" => (int) $out['H'],
      "tm_mday" => (int) $out['d'],
      "tm_mon"  => $out['m']?$out['m']-1:0,
      "tm_year" => $out['Y'] > 1900 ? $out['Y'] - 1900 : 0,
    );
    return $ret;
  }


  static function add($time, $dd=0, $mm=0, $YY=0){
    list($d, $m, $Y, $H, $i, $s) = explode(',',date('d,m,Y,H,i,s',$time));
    return mktime($H, $i, $s, $mm+$m, $dd+$d, $YY+$Y);
  }


  static function uday($time, $user_tz = false){
    if($user_tz) $time += exyks::retrieve("USER_TZ");
    return floor($time/86400);
  }


    /** get universal day **/
  static function mkuday($d=false, $m=false, $y=false){
    return self::uday(gmmktime(0, 0, 0,
        $m!==false?$m:idate('m'),
        $d!==false?$d:idate('d'),
        $y!==false?$y:idate('Y')));
  }


  static function sprintf($date=_NOW, $format=DATE_MASK){
    return self::sprintfc($date, preg_replace("#[a-z]#i",'$$0', $format));
  }


  static function sprintfc($date=_NOW, $format=DATE_DAY, $format_rel=false){
    return self::sprintfc_time($date, $format, $format_rel);
  }

  static function sprintfc_time($date=_NOW, $format=DATE_DAY, $format_rel=false, $time = true){
    if(is_null($date))
        return "&date.undefined;";
    if($date==0)
        return "&date.0;";
    if($date==2147483647)
        return "&date.never;";

    static $rs=false;
        if(!$rs) $rs=array(
            date('z/Y',_NOW)=>'&date.today;',
            date('z/Y',_NOW-86400)=>'&date.yesterday;');


    static $USER_TZ = false;
        if($USER_TZ===false && class_exists("exyks") && $time)
            $USER_TZ = exyks::retrieve("USER_TZ");

    $datef = $date+$USER_TZ; //date to display (use client TZ to format)
    list($d, $m, $n, $Y, $H, $i, $s, $z, $N, $j) = explode(',',date("d,m,n,Y,H,i,s,z,N,j",$datef));
    $p = $USER_TZ/3600; $p = ($p>0?'+':'').substr("0$p", -2);$P = "{$p}:00"; $O="{$p}00";
    //P=+02:00 O=+0200

    if($z<79 or $z>354)$a=4; elseif($z<172)$a=1; elseif($z<265)$a=2; else $a=3; //a = season


    $t=ceil($n/3); $rel=$rs["$z/$Y"];
    $query = $rel&&$format_rel?$format_rel:$format;
    return preg_replace(VAR_MASK, VAR_REPL, $query);
  }


  public static function human_diff($timestamp, $max = 2){

    $steps = array(
      's'   => 60,
      'min' => 60,
      'hour' => 24,
      'day' => 30,
      'month' => 12,
      'year' => 0,
    );

    $out = array('s' => '0 s');
    foreach($steps as $name => $step_time){
      if($step_time == 0){
        $current = floor($timestamp);
      }else{
        $current = $timestamp % $step_time;
        $timestamp /= $step_time;
      }
      if($current > 0)
        $out[$name] = "$current $name".($name != 's' && $current > 1 ? 's' : '');
    }
    return implode(' ', array_slice(array_reverse($out), 0, $max));
  }


  /**
  * Le jour est-il férié
  *
  * @param int $timestamp
  */
  static function is_dayoff($timestamp) {
    $is_holyday = false;

    // Week end (Samedi/Dimanche)
    $week_day = date("N", $timestamp);
    if($week_day == 6  || $week_day == 7)
      $is_holyday = true;

    $day = date("d", $timestamp);
    $month = date("m", $timestamp);
    $year = date("Y", $timestamp);

    // Dates fériées fixes
    if($day == 1 && $month == 1)   $is_holyday = true; // 1er janvier
    if($day == 1 && $month == 5)   $is_holyday = true; // 1er mai
    if($day == 8 && $month == 5)   $is_holyday = true; // 8 mai
    if($day == 14 && $month == 7)  $is_holyday = true; // 14 juillet
    if($day == 15 && $month == 8)  $is_holyday = true; // 15 aout
    if($day == 1 && $month == 11)  $is_holyday = true; // 1 novembre
    if($day == 11 && $month == 11) $is_holyday = true; // 11 novembre
    if($day == 25 && $month == 12) $is_holyday = true; // 25 décembre

  // date fériées mobiles
  // Pâques
  /* @TODO : Pas de module pour easter_date d'installé !!!!!

    $easter = @easter_date($annee);
    $jour_paques = date('d',$easter);
    $mois_paques = date('m',$easter);
    if($jour_paques == $jour && $mois_paques == $mois)
      $is_holyday = true;
    // Ascension
    $date_ascension = dateAddDay($easter,39);
    if(date('d',$date_ascension) == $jour && date('m',$date_ascension) == $mois)
      $is_holyday = true;
    // Pentecote
    $date_pentecote = dateAddDay($easter,50);
    if(date('d',$date_pentecote) == $jour && date('m',$date_pentecote) == $mois)
      $is_holyday = true;
      */

    return $is_holyday;
  }

  /**
  * Calcule la date dans X jours ouvrés.
  * Example : Vendredi + 2 jours ouvrés = mardi.
  *
  * @param int $date
  * @param int $open_days
  * @return $date + $open_days * day_duration
  */
  static function compute_date_with_openday($date, $open_days) {
    $cpt = 0;
    while($cpt < $open_days) {
      $date += 3600 * 24;// add a gap day.
      if(self::is_dayoff($date)) continue ; // on avance d'un gap day, mais pas d'un open day !
      $cpt++;
    }
    return $date;
  }


}

