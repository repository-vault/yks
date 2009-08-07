<?

class window extends __native {
  private $browser;
  private $url;
  public $document;

  function __construct($browser){
    $this->browser = $browser;

  }

  function go($url, $method = 'GET', $data  = array(), $enctype =false) {

    $url = ($url instanceof url)?$url:new url($url);
    if(is_null($this->url))
        $this->url = $url;
    else {
        $this->referer = $this->url;
        $this->url = $this->url->merge($url); //history HERE
    }
    if(!$this->url->is_browsable)
        throw new Exception("Invalid url");


    $lnk = $this->browser->get_lnk($this->url);

    $headers = array();
    if($this->referer) $headers["Referer"] = (string)$this->referer;

        $query = new request($this->url, $method, $data, $enctype );
        $query->addHeaders($headers);
        $lnk->execute($query);

    $content_type = $lnk->response['headers']['Content-Type'];
        //could abort 

    if($lnk->url != $this->url)//has been redirected
        $this->url = $lnk->url;
    $str = $lnk->receive();
 
    $charset = strtolower($content_type->extras['charset']);

    $this->document = new document($this, dom::simplexml_load_html($str, $charset) );

    if(!$charset){
        $this->get_charset();
    }

  }

  function get_charset(){
    return $this->document->charset;
  }

  function submit($form, $data=array()){
    $enctype = (string)$form['enctype'];
    $data = array_merge($form->toQueryString(), $data);

    $action  = (string)$form['action'];
    if(!$action) $action  = $this->url;
    $this->go($action, "POST", $data, $enctype );
  }


}