<?


class mime_part {
  private $mail;
  private $boudary;
  private $headers;
  private $contents;
  private $children;
  private $type_primary;
  private $type_extension;
  public $transfer_encoding;
  public $file_name;

  function __construct($mail, $part_infos){
    $this->mail = $mail;
    $content_type = $part_infos['content-type'];

    $this->transfer_encoding = "quoted-printable";
    list($this->type_primary,$this->type_extension)=explode('/',$content_type,2);

    if($this->is_composed())
        $this->boundary  = substr($part_infos['part_id'].'-'.md5($part_infos['part_id']._NOW),0,32);

    $this->contents = $part_infos['part_contents'];

  }

  function add_child(mime_part $child){
    $this->children[] = $child;
  }

  function apply_context(){
    if(!$this->is_textual()) return ;

    $context = (array) $this->mail->vars_list; extract($context);
    $this->contents = preg_replace(VAR_MASK,VAR_REPL,$this->contents);

    if($this->type_extension=="plain") //escape in no longer necessary
        $this->contents = specialchars_decode($this->contents);
  }

  function add_file($file_path, $file_name=false){
    if(!is_file($file_path )) return false;


    $part_infos  = array(
        'content-type'=>mime_content_type($file_path), //depends on file_ext
        'part_contents'=>file_get_contents($file_path),
    );

    $child_part = new mime_part($this->mail, $part_infos);
    if($file_name) $child_part->file_name = $file_name;
    $child_part->transfer_encoding = "base64";
    $this->add_child($child_part);
  }

  function is_composed(){
    return $this->type_primary  == "multipart";
  }

  function is_textual(){
    return $this->type_primary == "text";
  }

  function headers_output(){
    $str="";
    $str.= "Content-Type: $this->type_primary/$this->type_extension";
    if($this->is_textual())
        $str.=";charset=\"UTF-8\"";
    if($this->is_composed())
        $str.=";boundary=\"$this->boundary\"";
    if($this->file_name)
        $str.=";name=\"$this->file_name\"";
    $str.=CRLF;

    $str.=  "Content-Transfer-Encoding: $this->transfer_encoding".CRLF;

    if($this->type_primary =="image") {
        
    }
    if($this->file_name) {
        $str.="Content-Disposition:attachment;filename=\"$this->file_name\"".CRLF;
    }
    return $str.CRLF;
  }



  function encode(){
    $str="";
    $str.=$this->headers_output();

    if($this->is_composed()){
        $str.="There is no contents in a multipart";

        foreach($this->children as $child_part ){
            $str.=CRLF."--$this->boundary".CRLF;
            $str.=$child_part->encode();
        }
    } else {
        $this->apply_context();
        $str .= rfc_2047::encoding_encode($this->contents, $this->transfer_encoding);
    } return $str;
  }

}


