<?

class document extends native {
  private $_dom;
  private $_win;
  protected $data; //charset

  function __construct($window, $dom, $charset = false){
    if($charset) $this->data['charset'] = $charset;
    $this->_win = $window;
    $this->_dom = new domElementWrapper($this, $dom);
  }


  function get_documentElement(){
    return $this->_dom;
  }

  function get_window(){
    return $this->_win;
  }

  function __call($method, $args){
    return call_user_func_array(array($this->documentElement,$method), $args);
  }

  function getElementById($id){
    return $this->documentElement->getElementById($id);
  }

  function get_charset(){
    $res = $this->head->getElement('meta[http-equiv="Content-Type"]');
    $res = header::parse_extras($res['content']);
    return $res['charset'];
  }

  function get_body(){
    return $this->documentElement->getElement("body");
  }

  function get_head(){
    return $this->documentElement->getElement("head");
  }
}

