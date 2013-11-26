<?php
/**
* Represent Image in word Template
* @property $node DOMElement
* @property string $id Unique Id for image in document
* @property string $name image name
* @property string $descr image description
* @property sting $embed represent rId in (document.xml.rels)
*/
class WordTemplateImg{
  public $id;
  public $node;
  protected $descr;
  protected $name;
  protected $embed;
  protected $path;

  /**
  * Contrust form DOMNode
  * detect info in $node
  *
  * @param DOMElement $node
  * @return WordTemplateImg
  */
  public function __construct(DOMElement $node){
    $this->node = $node;
    $this->getInfo();
  }

  public function getDescr(){
    return $this->descr;
  }

  public function getName(){
    return $this->name;
  }

  public function getEmbed(){
    return $this->embed;
  }

  public function getPath(){
    return $this->path;
  }

  public function detectPath($rel_dom_xpath){
    $path_list = $rel_dom_xpath->query("//ns:Relationship[@Id='".$this->embed."']");
    if($path_list->length == 1){
      $this->path = $path_list->item(0)->getAttribute('Target');
    }
  }

  /**
  * Detect info($name, descr, ...) in $node
  *
  */
  protected function getInfo(){
    /** @var $info DOMElement[] */
    $info = $this->node->getElementsByTagNameNS(WordNS::WP, 'docPr');
    if($info->length > 0){
      $info = $info->item(0);
      $this->name = $info->getAttribute('name');
      $this->descr = $info->getAttribute('descr');
      $this->id = $info->getAttribute('id');
    }

    $info = $this->node->getElementsByTagNameNS(WordNS::A, 'blip');
    if($info->length > 0){
      $info = $info->item(0);
      $this->embed = $info->getAttributeNS(WordNS::R, 'embed');
    }
  }

  /**
  * Set Embed Value for image
  *
  * @param strig $value (ex : rId8)
  */
  public function setValue($value){
    /** @var $blip_list DOMElement[] */
    $blip_list = $this->node->getElementsByTagNameNS(WordNS::A, 'blip');

    $blip = $blip_list->item(0);

    $blip->setAttributeNS(WordNS::R, 'embed', $value);
    $this->embed = $value;

  }
}