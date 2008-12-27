<?

class source {

  static function process($doc, $node){
    $mode = (string)$node->getAttribute('mode');
    $mode = $mode?$mode:'block';
    $text=  trim($node->textContent);
    if(substr($text,0,2)!='<?')$text= "<?php".CRLF.$text;
    $text = highlight_string($text,true);
    $text = strtr($text, array('&nbsp;'=>'&#160;'));
    $add = simplexml_load_string($text);
    $add = dom_import_simplexml($add);
    $add->setAttribute('class', $mode);
    $add = $doc->importNode($add, true);
    $node->parentNode->replaceChild($add, $node );
  }

}

