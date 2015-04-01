<?php

class crawler extends window {
  protected $urls = array();

    //extend go to stack urls
  function go($url, $method = 'GET', $data = array() ){
    $this->urls[$url] = true;

    parent::go($url, $method, $data);

    $str = $this->document->asXML();
    return $this->parse_contents($str, $url)?$url:false;
  }
  
  function parse_contents($str){
    return false;
  }

  function walk(){
    return first(array_keys($this->urls, false));
  }

  function skip($url){
    $this->urls[$url] = true;
  }

  function scan_for_pages(){
  
  
    $links_dom = $this->document->getElements("a[href^='/']");//, a[href^='v']
    foreach($links_dom as $a){
        $url = $this->parse_url((string) $a["href"]);
        $this->enqueue_url($url);
    }
    print_r($this->urls);
  }
  function enqueue_url($url){
    if($url && !$this->urls[$url])
            $this->urls[$url] = false;
  }

  function parse_url($url) { return $url; }
}
