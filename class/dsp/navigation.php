<?php

class navigation{
  private $conf = null;
  public $pages = array();
  
  public function __construct($conf, $href = false){
    $this->conf = $conf;
    $this->load($href);
  }
  
  private function load($href){
    
    $user_tree = pick(sess::$sess['users_tree'], array());
    
    foreach($this->conf->page as $page){
      
      if($page->children()){
        $valid = true;
        //Check rights
        foreach($page->right as $right){
          $mode = pick((string)$right['mode'], 'access');
          if(!auth::verif((string)$right, $mode)){
            $valid = false;
            break;
          }
        }
        
        //Check distributor
        foreach($page->distributor as $distributor){
          $v = (string)$distributor;
          if(!$v) continue;
          if(in_array($v, $user_tree)){
            break; // valid
          }
          $valid = false; // not valid
        }
        
        if(!$valid) continue;
      }

      //Add page
      $url = (string)$page['url'];
      $this->pages[] = array(
        'url'   => $url,
        'help'  => (string)$page['help'],
        'name'  => (string)$page['name'],
        'active' => starts_with($href, starts_with($url, '/?') ? substr($url, 2) : $url),
      );
    }
  }
}