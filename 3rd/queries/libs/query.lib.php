<?php


class query {

  private $sql_query;
  private $data_results;
  private $cols;

  function __construct($sql_query) {

    $this->sql_query = $sql_query;
  }

  public function execute(){


    $res = sql::query($this->sql_query);
    if($res === false)
        throw new Exception("Query failed");

    $this->cols = array();
      for ($i = 0, $max=pg_num_fields($res); $i < $max; $i++) {
        $this->cols[$fieldname = pg_field_name($res, $i)] = array(
            'name'=>$fieldname ,
            'type'=>pg_field_type($res, $i),
        );
      }

    sql::reset($res);
    $this->data_results = sql::brute_fetch();

  }

    //direct output, use ob_start if required
  public function print_html_table_data(){
    echo "<table class='table center' style='width:100%'>
        <tr class='line_head'>";
    foreach($this->cols as $col_key=>$tmp)
        echo "<th>$col_key</th>";
    echo "</tr>";

    foreach($this->data_results as $line){
      echo "<tr class='line_pair'>";
      foreach($this->cols as $col_key=>$col_infos)
        echo "<td>{$line[$col_key]}</td>";
      echo "</tr>";
    }
    if(!$this->data_results)
        echo "<tfail>La requete n'a retourné aucun resultat</tfail>";
    echo "</table>";
  }

  public static function fast_export($sql_query){
    $query = new self($sql_query);
    $query->execute();

    ob_start();
    $query->print_html_table_data();
    $str = ob_get_contents();
    ob_end_clean();

    exyks_renderer_excel::render($str);
  }

  public function __toString(){
    return $this->sql_query;
  }



}