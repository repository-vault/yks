<?

class view extends view_base  {

  private $rules; //rules only exists in this driver
  private $privileges;

  function __construct($view_xml){
    parent::__construct($view_xml);
    $this->rules = new rules($view_xml->rules, $this);
    $this->privileges  = new privileges($view_xml->grants, $this->view_uname, 'view');
  }

  function sql_infos(){
    parent::sql_infos();
    $this->privileges->sql_infos();
    $this->rules->sql_infos();
  }

  function xml_infos(){
    parent::xml_infos();
    $this->privileges->xml_infos();
    $this->rules->xml_infos();
  }


  function modified(){
    return parent::modified()
        || $this->privileges->modified()
        || $this->rules->modified();
  }

  function update(){
    return array_merge(
        parent::update(),
        $this->privileges->alter_def(),
        $this->rules->alter_rules()
    );
  }
}
