<?

 //wrapper around simpleXMLElement
class domElement extends __wrapper {
  protected $base_type = "Element";
  public $document;

  function __construct(document $document, $dom){
    $this->document   = $document;
    parent::__construct($dom);
  }

  protected function __extend($from){
    return new self($this->document, $from);
  }

  function submit($data = array() ){
    //if($data) $this->set($data);
    $this->document->window->submit($this, $data);    
  }

}

