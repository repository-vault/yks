<?php

class exyks_renderer_excel {

  private static $XSL_SERVER_PATH;
  private static $XSL_TPL_TOP    = "Yks/Renderers/excel_top";
  private static $XSL_TPL_BOTTOM = "Yks/Renderers/excel_bottom";
  static $creator = 'Anonymous';

  static function init(){
    self::$XSL_SERVER_PATH = RSRCS_PATH."/xsl/specials/excel.xsl";
  }

  static function process(){ //prepare exyks rendering engine
    tpls::register_custom_element("table[contains(@class,'table')]", array(__CLASS__, 'extract_data'));
  }

  /**
  * Creation d un xml puis generation excel grâce à un tableau html.
  * Attention die à la fin de la methode
  *
  * @param DOMDocument $doc
  * @param DOMDocument $table_xml
  */
  const styles = "
    .header {
        font-weight:bold;
        background-color:#AEAEAE;
        font:Calibri 11;
    }
    .cell {
      font:Calibri 9;
      vertical-align:middle;
      border:1px solid #000000;
    }
  ";

  public static function extract_data($doc, $table_xml){

    $out_xml = new DOMDocument('1.0', 'utf-8');
    $root_xml = $out_xml->createElement("data");
    $root_xml->appendChild($out_xml->createElement('style'))
      ->appendChild($out_xml->createTextNode(self::styles));

    $worksheet = $out_xml->createElement('Worksheet');
    $worksheet->setAttribute('Name', pick(exyks::$head->title, 'Page 1'));

    //Pour les datas
    foreach($table_xml->getElementsByTagName("tr") as $row){
      $is_header  = (bool) $row->getElementsByTagName('th')->length ;
      $xml_row = $out_xml->createElement('Row');
      foreach($row->childNodes   as $td){

        $cell = $out_xml->createElement('Cell');
        $cell->setAttribute('class', $is_header ? 'header cell' : 'cell');

        if($td->nodeValue){
          $cell->appendChild($out_xml->createTextNode($td->nodeValue));
          $cell->setAttribute('Type', 'String');
        }

        $xml_row->appendChild($cell);
      }

      $worksheet->appendChild($xml_row);
    }

    $root_xml->appendChild($worksheet);
    $out_xml->appendChild($root_xml);
    $xml_to_xlsx = new xml_to_xlsx($out_xml);

    $xml_to_xlsx->create();
    $xml_to_xlsx->set_creator(self::$creator);

    header(sprintf(HEADER_FILENAME_MASK, exyks::$head->title.".xlsx")); //filename
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $safe_name = files::safe_name(exyks::$head->title.".xlsx");
    $file_path = files::tmpdir().DIRECTORY_SEPARATOR.$safe_name;

    $xml_to_xlsx->save($file_path);
    echo file_get_contents($file_path);
    unlink($file_path);
    die;
  }

  public static function render($str){
    self::process();
    $str = file_get_contents(tpls::tpl(self::$XSL_TPL_TOP))
          .$str
          .file_get_contents(tpls::tpl(self::$XSL_TPL_BOTTOM));
    exyks::render($str);
    die;
  }
  public static function build_table($table_contents, $headers = array(), $multiline_style = true){

    $table_xml = "<table class='table'>";
    if(!$headers) $headers = array_combine($headers = array_keys(current($table_contents)), $headers);

    $med_size = array();

    foreach($table_contents as $line){
        $k=0;
        foreach($line as $val) {
            $len = strlen($val);
            if($len) $med_size["col_".$k][] = $len;
            $k++;
        }
    }

    $table_xml .= "<tr class='line_head'>"; $col_count=0;
    foreach($headers as $col_key => $col_name) {
        $col_key = preg_replace("#[^a-z0-9_-]#","", strtolower($col_key));
        $col_id = "col_".($col_count++);

        $strlen = $med_size[$col_id] ?array_sum($med_size[$col_id])/count($med_size[$col_id]) : 10;
        $width  = max(15, $strlen * 7); //pt
        $width = $multiline_style?"":"width='{$width}'";

        $table_xml .= "<th $width class='col_$col_key $col_id' id='$col_id'>$col_name</th>";
    } $table_xml .="</tr>";

    foreach($table_contents as $line) {
        $str = "<tr class='line_pair'>";
        foreach($headers as $col_key=>$v)
            $str .="<td>{$line[$col_key]}</td>";
        $str .= "</tr>";
        $table_xml .= $str;
    } if(!$table_contents)
        $table_xml.="<tfail>no results</tfail>";
    $table_xml .="</table>";

    return $table_xml;

  }


    //deprecated, use render instead
  public static function build_xls($table_contents, $headers = array(), $styles=""){
    $table_xml = self::build_table($table_contetns, $headers);

    $xml_contents = "<body xmlns:xls='excel'>
        <xls:style xmlns:xls='excel'>$styles</xls:style>
        $table_xml
    </body>";

    $doc = new DOMDocument('1.0','UTF-8');
    $tmp = $doc->loadXML($xml_contents, LIBXML_YKS);
    $doc   = xsl::resolve($doc, self::$XSL_SERVER_PATH);
    $contents = $doc->saveXML();


    $contents = strstr($contents, '<html');
    return $contents;
  }


}