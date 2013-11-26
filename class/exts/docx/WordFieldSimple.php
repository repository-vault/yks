<?php
  /**
*@property DOMElement $node
*/
class WordFieldSimple extends WordField{
  public function __construct($brute_name, $node){
    parent::__construct($brute_name, $node);
    $this->type = self::SIMPLE;
  }

  public function getValue(){
    $this->value = $this->node->textContent;
    return $this->value;
  }

  public function cleanNode(DomDocument $dom){
    $wr_list = $this->node->getElementsByTagNameNS(WordNS::W, 'r');

    while($wr_list->length > 1){
      $this->node->removeChild($wr_list->item(1));
    }

    return $wr_list->item(0);
  }
}
