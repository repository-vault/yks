<?php 

/**
*  A rules collection group against a table ref
*/

class rules extends myks_collection {

  private $parent;
  private $parent_type;

  private $drops = array();

  function __construct($parent, $rules_collection, $parent_type) {
    $this->parent      = $parent;
    $this->parent_type = $parent_type;


    foreach($rules_collection as $rule_xml){
        $rule = new rule($parent, $rule_xml, $parent_type);
        $this->stack($rule);
    }


  }


  function modified(){
    return parent::modified()
        || count($this->drops)
    ;
  }

  function sql_infos(){
    parent::sql_infos();

    //look for droppable

    $rules = rule::sql_search($this->parent, $this->parent_type);
    foreach($rules as $rule) {
        if($this->contains($rule)) continue;
        $this->drops[] = $rule;
    }

  }

  function alter_def(){
    $ret = parent::alter_def();
    foreach($this->drops as $rule)
        $ret = array_merge($ret, $rule->delete_def());
    return $ret;
  }

}