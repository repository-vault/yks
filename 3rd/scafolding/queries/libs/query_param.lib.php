<?

class query_param extends _sql_base {

  
  const sql_table = 'ks_queries_params_list';
  protected $sql_table = 'ks_queries_params_list';
  protected $sql_key = "param_id";
  const sql_key = "param_id";




  static function from_where($where){
    return parent::from_where(__CLASS__, self::sql_table, self::sql_key, $where);
  }

  public function trash(){
    $this->sql_delete();
    return null;
  }


  public static function create($data, $_POST){

    try {
        $data = self::verify_input($data, $_POST);
        $param_id = sql::insert(self::sql_table, $data, true);

        return new self($param_id);
    } catch(Exception $e) { throw rbx::error("Construction error"); }

  }


  public function verify_input($data, $_POST){
    $param_type = $data['param_type'];
    if($param_type == 'query')
        self::valid_query($data, $_POST);
    if($param_type == 'char') ;
    else 
        throw rbx::error("Unsupported param type");

    return $data;
  }


  public function __toString(){
    return $this->param_key;
  }

  public function valid_query(&$data, $_POST){

    $sub_query_str = $_POST['query_contents'];
    $data['param_arg0'] = $sub_query_str;

    $test = sql::qrow($sub_query_str);
    if(!(isset($test['title']) && isset($test['value'])))
        throw rbx::error("Votre sous requete a echoué");
  }


  public function format_input(){

    $param_nullable = bool($this->param_multiple);
    $param_multiple = bool($this->param_multiple);

    if($this->param_type == 'char') {
        $str = "<field title='{$this->param_key}' name='{$this->param_key}'";

        if($param_multiple)
            $str .= " type='textarea'/><p>Vous pouvez specifier une valeur par ligne</p>";
        else $str .= " type='string'/>";
    }elseif($this->param_type == 'query') {
        $query = $this->param_arg0;

        sql::query($query);
        $values = sql::brute_fetch('value', 'title');
        asort($values);
        $str = "<field title='{$this->param_key}'>";
        if($param_multiple) {
            $size = min(10, count($values));
            $str .= "<select name='{$this->param_key}[]' size='$size'>";
        } else $str .= "<select name='{$this->param_key}'>";

        if($param_nullable) $str .= "&select.choose;";

        $str .= dsp::dd($values);
        $str .= "</select></field>";
       
    }


    return $str;

  }


}