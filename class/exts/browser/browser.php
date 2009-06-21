<?

class browser {
  public $document;

  private $url;
  private $start_url;
  private $lnk;

  function __construct($url){
    $this->start_url = $url;
    $this->lnk = new sock_lnk($url);
    
  }
        //sock_lnk is connection:Keep-alive, closing is a good thing
  function close(){
    $this->lnk->close();
  }

  function go($url, $method = 'GET', $data  = array()){
    $this->url = $url;
    $this->lnk->request($this->url, array('method'=>$method), $data);

    $str = $this->lnk->receive();
    return $this->parse_contents($str);
  }

  function parse_contents($str){
    $this->document = simplexml_load_html($str);
  }

  function submit($form, $data = array()){
    if(!$form)
    if($data) $form->set($data);

    $queryString = $form->toQueryString();

    $action  = (string)$form['action'];
    if(!$action) $action  = $this->url; //if outside browser= dead
    $this->go($action, 'POST', $data);
  }

}

