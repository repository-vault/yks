<?

class bbwriter {
  const easy       =  '#\[(%s)\](.*?)\[/\\1\]#is';
  const url_mask   = "https?://[a-z_:0-9.-]+(?:/[\?\#\$a-z0-9!%&'()*+,-./:;=@^|~-]*)?";

  private static $trs   = array();
  private static $pregs = array();

  public static function init(){

    $size_mask  = "[0-9]+";
    $color_mask = "[a-z]+|\#(?:[0-9A-F]+)";
    $align      = "baseline|bottom|top|text-top|text-bottom|middle";

    self::register_preg(array(
        sprintf(self::easy, 'b|u|i|quote')      => '<$1>$2</$1>',
        sprintf(self::easy, 'strike')           => '<span style="text-decoration:line-through">$2</span>',
        sprintf(self::easy, 'justify|left|right|center')   => '<div style="text-align:$1">$2</div>',
        "#\[color=($color_mask)\](.*?)\[/color\]#is"      => '<span style="color:$1">$2</span>',

        "#\[url=(".self::url_mask.")\](.*?)\[/url\]#is"        => '<a class="ext" href="$1">$2</a>',
        "#\[url\](".self::url_mask.")\[/url\]#is"              => '<a class="ext" href="$1">$1</a>',
        "#\[img\](".self::url_mask.")\[/img\]#is"              => '<img src="$1"/>',
        "#\[img=($align)\](".self::url_mask.")\[/img\]#is"     => '<img style="vertical-align:$1" src="$2"/>',
        "#\[float=(right|left)\](.*?)\[/float\]#is"   => '<div style="float:$1">$2</div>',
        "#\[size=(([+-])\\2+)](.*?)\[/size]#ise"      => '"<span style=\"font-size:".(100 $2 strlen("$1")*20)."%\">$3</span>"',
        "#\[hr/]#is"       => '<hr class="clear"/>',
    ));

  }


  protected static function register_tr($trs){
    self::$trs = array_merge(self::$trs, $trs);
  }

  protected static function register_preg($pregs){
    self::$pregs = array_merge(self::$pregs, $pregs);
  }


  static function decode($txt){

    $txt = specialchars_encode($txt);
    $txt = strtr($txt, self::$trs);
    $tmp = null;

    while($txt != $tmp =
        preg_replace(array_keys(self::$pregs), array_values(self::$pregs), $txt))
            $txt = $tmp;

    $txt = nl2br(trim($txt));

    $txt = xml::clean_html($txt);
    $txt = specialchars_decode($txt);

    return $txt;
  }
  

}