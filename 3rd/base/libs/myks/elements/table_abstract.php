<?

class table_abstract extends myks_installer {

  protected $table;
  protected $table_keys;
  protected $table_fields;
  protected $abstact_xml;

  protected $view;
  protected $triggers;
  protected $procedures;


  function modified(){
    $modified = $this->view->modified()
           || $this->procedures->modified();
    foreach($this->triggers as $triggers)
        $modified |= $triggers->modified();
    return $modified;
  }

  function get_name(){
    return $this->table->get_name();
  }

  function alter_def(){
    $ret = array();
    $ret = array_merge($ret, $this->procedures->alter_def());
    $ret = array_merge($ret, $this->view->alter_def());

    foreach($this->triggers as $triggers)
      $ret = array_merge($ret, $triggers->alter_def());
    return $ret;
  }

  function xml_infos(){
    $this->view->xml_infos();
    $this->procedures->xml_infos();

    foreach($this->triggers as $triggers)
      $triggers->xml_infos();
  }

  function sql_infos(){
    $this->view->sql_infos();
    $this->procedures->sql_infos();
    foreach($this->triggers as $triggers)
      $triggers->sql_infos();
  }

  function delete_def(){
    $ret = array_merge(
        $this->view->delete_def(),
        $this->procedures->delete_def(),
        $this->triggers->delete_def()
    );
    foreach($this->triggers as $triggers)
      $ret = array_merge($ret, $triggers->delete_def());

    return $ret;
  }


}
