<?

class css_ruleset {
  private $selector; //string for now
  private $block;

  function __construct($selector, $block) {
    $this->selector = $selector;
    $this->block    = $block;
  }
  
  function output(){
    $str = "";
    $str .= $this->selector;
    $str .= $this->block->output();
    return $str;
  }

}