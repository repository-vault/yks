<?php

// not implemented : ns support / mixed elements

class KsimpleXMLParser {
  private $class = 'KsimpleXMLElement';
  private $parser;
  private $node;
  private $depth;
  private $nodes_path;


  public function __construct($class){
    if(!is_null($class)) $this->class  = $class;
    $this->parser = xml_parser_create();
    $this->node   = null;
    $this->depth  = 0;
    $this->nodes_path = array();
    $this->nodes_contents = array();

    xml_set_object($this->parser, $this);
    xml_set_element_handler($this->parser, "tag_open", "tag_close");
    xml_set_character_data_handler($this->parser, "cdata");
    xml_set_default_handler($this->parser, "std");
    xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
  }



  public function process($str){
    xml_parse($this->parser, $str);
    return $this->node; 
  }

  private function tag_open($parser, $name, $attribs) {
    $class = $this->class;
    $node = new $class($name, $attribs);

    if(!$this->node) {
        $this->node = $this->nodes_path[0] = $node;
        return;
    }

    $this->nodes_path[$this->depth+1] = $node;
    $this->nodes_path[$this->depth++]->adopt($node);

    $this->nodes_contents[self::hash($node)] = null;
  }

  private function tag_close($parser, $name) {    
    $this->depth--;
  }

  private function cdata($parser, $str) {

    $node = $this->nodes_path[$this->depth];
    $UID  = self::hash($node);

    if( !trim($str) //empty nodes are skipped
        && is_null($this->nodes_contents[$UID]))
        return;

    $this->nodes_contents[$UID] .= $str;
    $node->set(trim($this->nodes_contents[$UID]));
  }

  static private function hash($node){
    return spl_object_hash($node);
  }

  private function std($parser, $str) {
    // ?
  }

}
