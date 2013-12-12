<?php
  /**
* Represent table in template
*
* @property $name string
* @property $field_list WordField[] list of include field
* @property $row DOMElement
*/
class WordTemplateTable {
  public $name;
  protected $row;
  protected $field_list;

  /**
  * Create new WordTemplateTable with name
  *
  * @param string $name
  * @return WordTemplateTable
  */
  public function __construct($name){
    $this->name = $name;
  }

  /**
  * @return WorField[]
  *
  */
  public function getFieldList(){
    return $this->field_list;
  }

  /**
  * Add a WordField to table field_list
  *
  * @param WordField $field
  */
  public function addField(WordField $field){
    $this->field_list[$field->name] = $field;
  }

  /**
  * Set value and create lines in table if needed
  *
  * @param DOMDocument $dom
  * @param array $value (ex: array(0 //line => array(col1 => 1, col2 => 3)))
  */
  public function setValue(DOMDocument $dom, $value){
    //detect first row
    $this->detectRow();
    $i = 0;
    //lines
    foreach($value as $tr){
      foreach($this->field_list as $field){
        $field->setValue($dom, "");
      }

      //cells
      foreach($tr as $col => $value){
        $this->field_list[$col]->setValue($dom, $value);
      }
      $i++;
      $clone = $this->row->cloneNode(true);

      $this->row->parentNode->insertBefore($clone, $this->row);
    }

    $this->removeRow();
  }

  /**
  * Remove row
  *
  */
  public function removeRow(){
    //remove example
    $this->detectRow();
    $this->row->parentNode->removeChild($this->row);
  }


  /**
  * Detect row for Field
  *
  */
  protected function detectRow(){
    /** @var $node DOMElement */
    $node = first($this->field_list);
    $node = $node->node->parentNode;
    while($node->nodeName != 'w:tr'){
      $node = $node->parentNode;
    }

    $this->row = $node;
  }
}