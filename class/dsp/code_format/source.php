<?

class source {

  static function process($doc, $node){
    $mode = (string)$node->getAttribute('mode');
    $mode = $mode?$mode:'block';

    $code = (string)$node->getAttribute('code');
    $code = $code?$code:'php';


    if($code == "xml")
        $text = self::render_xml($node->textContent);
    else {
        $text = self::render_php($node->textContent);
    }

    $add = simplexml_load_string($text);
    $add = dom_import_simplexml($add);
    $add->setAttribute('class', $mode);
    $add = $doc->importNode($add, true);
    $node->parentNode->replaceChild($add, $node );
  }


  private static function render_php($str){
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

  private static function render_xml($str){
    require_once "highlight_xml.php";
    $res = highlight_xml::highlight($str);
    $res = sprintf("<code>%s</code>", $res);
    return $res;
  }

}
