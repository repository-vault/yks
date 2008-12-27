<?

class yks_list {
  public $href;
  public $target;
  public $table_name;
  private $filters;
  public $results;
  public $table_index;
  private $by=20;
  private $page_id=0;

  private $results_nb=0;


  function __sleep(){
    $this->table_xml = $this->table_xml->asXML();
    return array('results','table_name','href','target','table_index','table_xml','results_nb','filters','order_by');
  }
  function __wakeup(){
    $this->table_xml = simplexml_load_string($this->table_xml);
  }

  function __construct($table_name, $filters = true){

    $this->table_name = $table_name;

        //retourne le nom de la clée primaire de la table

    $types = yks::$get->types_xml;
    $xpath  ="//*[@birth='{$this->table_name}']";
    $this->table_index=current($types->xpath($xpath))->getName();
    $this->table_xml = yks::$get->tables_xml->$table_name;

    if(!$filters['filter_context']) $filters['filter_context'] = array();
    $this->filters = $filters;

    $this->order_by($this->table_index);

    $this->filters_apply($this->filters['filter_initial']);

  }
 
  function filters_apply($filters){
    if(!is_array($filters)) $filters = array();
    $this->filters['filters_results'] = array_merge($this->filters['filter_context'], $filters);
    $this->build_list();
  }

  function build_list(){
    sql::select($this->table_name, $this->filters['filters_results'], $this->table_index);
    $liste = sql::brute_fetch(false, $this->table_index);
    $this->results = $liste;
    $this->results_nb = count($liste);
  }
  function order_by($field=false,$way="DESC"){
    $this->order_by = array($field?$field:$this->table_index=>$way);
  }
  function get_infos(){
    $order_by =array();
    foreach($this->order_by as $field=>$way)$order_by[]="`$field` $way";
    $order_by = $order_by?"ORDER BY ".join(',', $order_by):'';
    $filters = array( $this->table_index=> $this->results);
    $start = $this->page_id * $this->by;
    sql::select($this->table_name,  $filters, "*", "$order_by LIMIT $this->by OFFSET $start");
    return sql::brute_fetch($this->table_index);
    
  }
  function repage(){
    jsx::js_eval("Jsx.open('$this->href', '$this->target', this)");
  }
  function page_set($page_id){ $this->page_id = $page_id; }
  function navigation_show(){
    return dsp::pages($this->results_nb, $this->by, $this->page_id, "$this->href//", $this->target);
  }
}

