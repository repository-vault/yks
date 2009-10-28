<?php

class source {

  static function process($doc, $node){
    $mode = (string)$node->getAttribute('mode');
    $mode = $mode?$mode:'block';

    $code = (string)$node->getAttribute('code');
    $code = $code?$code:'php';

    $file = (string) $node->getAttribute('file');
    $text = $node->textContent;
    if(is_file($file))
        $text = file_get_contents($file);

    if($code == "xml")
        $text = self::render_xml($text);
    elseif($code == "js")
        $text = self::render_js($text);
    else {
        $text = self::render_php($text);
    }

    $add = simplexml_load_string($text);
    $add = dom_import_simplexml($add);
    $add->setAttribute('class', $mode);
    $add = $doc->importNode($add, true);
    $node->parentNode->replaceChild($add, $node );
  }
        //js is nothing but PHP (for now..)
  public static function render_js($str){
    $str = preg_replace("#\r?\n#", "",self::render_php($str));//(?<=^ powa
    $str = preg_replace("#(?<=^<code><span class='php_html'>)"
        ."<span class=.php_default.>&lt;\?php.*?</span>#s","", $str);
    $str = preg_replace("#(?<=class=')php_#", "js_", $str);
    return $str;
  }

  public static function render_php($str){
    $str = trim($str);
    if(substr($str ,0,2)!='<?') $str= "<?php".CRLF.$str;
    $str = highlight_string($str ,true);

    $colors_to_class = array(
        'DD0000'=>'php_string',
        'FF8000'=>'php_comment',
        '007700'=>'php_keyword',
        '0000BB'=>'php_default',
        '000000'=>'php_html'
    );
    foreach($colors_to_class as $color=>$class)
        $strtr["<span style=\"color: #$color\">"] = "<span class='$class'>";
    $strtr['&nbsp;']='&#160;';
    $str = strtr($str, $strtr);

    return $str;
  }

  public static function render_xml($str){
    require_once "highlight_xml.php";
    $res = highlight_xml::highlight($str);
    $res = sprintf("<code>%s</code>", $res);
    return $res;
  }

}
