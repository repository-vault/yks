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

  public function print_html_table_data(){
    echo exyks_renderer_excel::build_table($this->data_results);
  }


  public static function fast_export($sql_query, $multiline = false){
    $query = new self($sql_query);
    $query->execute();

    if(!$multiline)
        $styles = "tr {mso-height-source:userset;height:12.0pt }";
    $table_xml = exyks_renderer_excel::build_table($query->data_results, false, $multiline);
    $str = "<body xmlns:xls='excel'>
        <xls:style xmlns:xls='excel'>$styles</xls:style>
        $table_xml
    </body>";



    exyks_renderer_excel::render($str);
  }

  public function __toString(){
    return $this->sql_query;
  }



}