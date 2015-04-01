<?php

class bbwriter {
  const easy       =  '#\[(%s)\](.*?)\[/\\1\]#is';
  const easynl       =  '#\[(%s)\](.*?)\[/\\1\](?:\r?\n)?#is';
  const url_mask   = "(?:https?://[a-z_:0-9.-]+)?(?:/[\?\#\$a-z 0-9!%&()*+,-._/:;=@^|~-]*)?";

  private static $trs   = array();
  private static $pregs = array();

  private static $options;

  public static function init(){

    $size_mask  = "[0-9]+";
    $color_mask = "[a-z]+|\#(?:[0-9A-F]+)";
    $align      = "baseline|bottom|top|text-top|text-bottom|middle";

    $nlf        = "(?:\r?\n)?";
    self::register_preg(array(
        sprintf(self::easy, 'b|u|i|quote')      => '<$1>$2</$1>',
        sprintf(self::easynl, 'h[12345]')         => '<$1>$2</$1>',
        sprintf(self::easy, 'strike')           => '<span style="text-decoration:line-through">$2</span>',
        sprintf(self::easynl, 'justify|left|right|center')   => '<div style="text-align:$1">$2</div>',
        "#\[color=($color_mask)\](.*?)\[/color\]#is"      => '<span style="color:$1">$2</span>',

        "#\[url=(".self::url_mask.")\](.*?)\[/url\]#is"        => '<a class="ext" href="$1">$2</a>',
        "#\[url\](".self::url_mask.")\[/url\]#is"              => '<a class="ext" href="$1">$1</a>',
        "#\[img\](".self::url_mask.")\[/img\]#is"              => '<img src="$1"/>',
        "#\[img=($align)\](".self::url_mask.")\[/img\]#is"     => '<img style="vertical-align:$1" src="$2"/>',
        "#\[float=(right|left)\](.*?)\[/float\]#is"   => '<div style="float:$1">$2</div>',
        "#\[size=([+-])([0-9]+)](.*?)\[/size]#ise"      => '"<span style=\"font-size:".(100 $1 $2*10)."%\">$3</span>"',
        "#\[size=(([+-])\\2*)](.*?)\[/size]#ise"      => '"<span style=\"font-size:".(100 $2 strlen("$1")*20)."%\">$3</span>"',

        "#\[hr/](?:\r?\n)?#is"       => '<hr class="clear"/>',
        "#\[clear/](?:\r?\n)?#is"       => '<clear/>',

        "#\[lines( *)](.*?)\[/lines]$nlf#ise"            => '"<div style=\"margin-left:".(strlen("$1")*10)."px\">$2</div>"',
        "#\[spoiler=(.*?)](.*?)\[/spoiler]$nlf#is"      => '<div class="sploiler closed toggle_zone"><div class="toggle_anchor">$1</div><div>$2</div></div>',
    ));

    $opt = yks::$get->config->bbwriter->options;
    self::$options['allow_html'] = bool($opt['allow_html']);
    self::$options['allow_php']  = bool($opt['allow_php']);
  }

  public static function split_more($str){
    return explode("[readmore/]", $str);
  }

  protected static function register_tr($trs){
    self::$trs = array_merge(self::$trs, $trs);
  }

  protected static function register_preg($pregs){
    self::$pregs = array_merge(self::$pregs, $pregs);
  }

/*
    allow_html
    allow_php
*/
  static function decode($txt, $options = array()){
    $options = array_merge(self::$options, $options);

    $safes = array();
    $safe = preg_match_all(sprintf(self::easynl, "html|php"), $txt, $out, PREG_OFFSET_CAPTURE|PREG_SET_ORDER);
    if($safe) foreach(array_reverse($out) as $id=>$match) {
        $key = "&safe.$id;" ;
        $safes[$match[1][0]][$key] = specialchars_decode($match[2][0]);
        $txt = substr($txt, 0, $start = $match[0][1]) . $key . substr($txt, $start + strlen($match[0][0]));
    }

    $txt = specialchars_encode($txt);
    $txt = strtr($txt, self::$trs);
    $tmp = null;

    while($txt != $tmp =
        preg_replace(array_keys(self::$pregs), array_values(self::$pregs), $txt))
            $txt = $tmp;

    $txt = nl2br(trim($txt));

    $txt = xml::clean_html($txt);
    $txt = specialchars_decode($txt);
    
    if($options['allow_html'] && $safes['html'])
        $txt = strtr($txt, $safes['html']);

    if($options['allow_php'] && $safes['php']) {
        $safes['php'] = array_map(array(__CLASS__, 'evaluate'), $safes['php']);
        $txt = strtr($txt, $safes['php']);
    }

    return $txt;
  }
  
  private static function evaluate($str){
    ob_start();
    eval('?>'.$str);
    $str = ob_get_contents();
    ob_end_clean();
    return $str;
  }
}