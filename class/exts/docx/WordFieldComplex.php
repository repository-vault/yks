<?php
  class WordFieldComplex extends WordField{
  public function __construct($brute_name, $node){
    parent::__construct($brute_name, $node->parentNode->nextSibling);
    $this->type = self::COMPLEX;
  }

  public function getValue(){
    $value = null;

    $node = $this->node;

    while(true){
      $node = $node->nextSibling;
      if(!$node) break;

      $fld_list = $node->getElementsByTagNameNS(WordNS::W, 'fldChar');

      for($i = 0; $i < $fld_list->length; $i++){
        if($fld_list->item($i)->getAttributeNS(WordNS::W, 'fldCharType') == 'end') break 2;
      }

      $value .= $node->textContent;
    }

    $this->value = $value;
    return $this->value;
  }

  public function cleanNode(DOMDocument $dom){
    /** @var DOMElement[] */
    $remove_list = array();
    $first_t = null;
    $node = $this->node;

    while(true){
      $node = $node->nextSibling;
      if(!$node) break;

      $fld_list = $node->getElementsByTagNameNS(WordNS::W, 'fldChar');

      for($i = 0; $i < $fld_list->length; $i++){
        $fldChartype = $fld_list->item($i)->getAttributeNS(WordNS::W, 'fldCharType');
        if($fldChartype == 'end'){
          break 2;
        }
      }

      //remove all br
      $t_list = $node->getElementsByTagNameNS(WordNS::W, 't');

      if($t_list->length > 0){
        if(!$first_t){
          $first_t = $node;

          //only one t
          while($t_list->length > 1){
            $node->removeChild($t_list->item(1));
          }
        }
        else{
          $remove_list[] = $node;
        }
      }
    }



    foreach($remove_list as  $remove_node){
      $this->node->parentNode->removeChild($remove_node);
    }

    //remove all br
    $br_list = $first_t->getElementsByTagNameNS(WordNS::W, 'br');
    while($br_list->length > 0){
      $first_t->removeChild($br_list->item(0));
    }

    return $first_t;
  }
}
