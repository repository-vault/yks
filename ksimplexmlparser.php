<?php

class KsimpleXMLParser {
  private $class = 'KsimpleXMLElement';
  private $parser;
  private $config;
  private $depth;
  private $nodes_path;


  public function __construct($class){
    if(!is_null($class)) $this->class  = $class;
    $this->parser = xml_parser_create();
    $this->config = null;
    $this->depth  = 0;
    $this->nodes_path = array();

    xml_set_object($this->parser, $this);
    xml_set_element_handler($this->parser, "tag_open", "tag_close");
    xml_set_character_data_handler($this->parser, "cdata");
    xml_set_default_handler($this->parser, "std");
    xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
  }



  public function process($str){
    xml_parse($this->parser, $str);
    return $this->config; 
  }

  private function tag_open($parser, $name, $attribs) {
    $class = $this->class;
    $node = new $class($name, $attribs);

    if(!$this->config) {
        $this->config = $this->nodes_path[0] = $node;
        return;
    }

    $this->nodes_path[$this->depth++]->adopt(
        $this->nodes_path[$this->depth] = $node
    );

  }

  private function tag_close($parser, $name) {    
    $this->depth--;
  }

  private function cdata($parser, $str) {
    $str = trim($str);
    if(!$str) return;
    $this->nodes_path[$this->depth]->set($str);
  }

  private function std($parser, $str) {
    // ?
  }

}