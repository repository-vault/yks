<?php
  /**
* @property string $name
* @property string $type (Complex, Simple)
* @property string $value
* @property DOMElement $node
*
* @property $xml_name
*/
abstract class WordField {

  public $name;
  public $type;
  public $value;
  public $node;
  protected $xml_name;

  const COMPLEX = 'complex';
  const SIMPLE  = 'simple';

  /**
  *
  * @param mixed $brute_name
  * @param mixed $node
  * @return WordField
  */
  public function __construct($brute_name, $node){
    $this->name = $this->extract_name($brute_name);
    $this->node = $node;
  }

  /**
  * Get the value from $node
  * and set $this->value
  *
  */
  public abstract function getValue();

  /**
  * Clean field before insert
  * @return DOMElement $node for set the value
  */
  public abstract function cleanNode(DOMDocument $dom);

  public function setValue(DOMDocument $dom, $value){
    $node = $this->cleanNode($dom);

    // add return line
    $value = preg_split("#(\r?\n)#", $value);
    if(count($value) == 1){
      $val = $value[0];
      $value = null;
    }
    else{
      $val = array_shift($value);
    }

    $t_list = $node->getElementsByTagNameNS(WordNS::W, 't');
    $first_node = $t_list->item(0);
    $attributes = $first_node->attributes;

    $first_node->nodeValue = trim($val);

    if(is_array($value)){
      foreach($value as $val){
        $br = $dom->createElementNS(WordNS::W, 'br');
        $first_node->parentNode->appendChild($br);

        $clone = $first_node->cloneNode();
        $clone->nodeValue = trim($val);

        $first_node->parentNode->appendChild($clone);
      }
    }
  }

  /**
  * Extract name from merge field document
  *
  * @param string $str (MERGEFIELD  myfield \* Upper  \* MERGEFORMAT)
  * @return string name for the mergeField (ex : myfield)
  */
  protected function extract_name($str){
    $this->xml_name = $str;

    $exploded = array_filter(explode(' ', trim($str)));
    $exploded = array_values($exploded); //reindex
    return str_replace('"', '', $exploded[1]);
  }
}