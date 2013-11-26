<?php

 //wrapper around simpleXMLElement
class domElementWrapper extends __wrapper {
  protected $base_type = "Element";
  public $document;


  static function init(){
    if(class_exists('classes') && !classes::init_need(__CLASS__)) return;

    Element::__register("Forms");
  }

  function __toString(){
    return parent::__toString();
  }

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
  function click(){
    $this->document->window->go((string) $this['href']);
  }
}

