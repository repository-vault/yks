<?php

class exyks_renderer_excel {

  static function process(){
    header(sprintf(HEADER_FILENAME_MASK, exyks::$head->title.".xls")); //filename
    exyks::$headers["excel-server"] = TYPE_CSV;
    exyks::store('XSL_SERVER_PATH', RSRCS_PATH."/xsl/specials/excel.xsl");
    exyks::store('RENDER_SIDE', 'server');
    exyks::store('RENDER_MODE', 'excel');
    exyks::store('RENDER_START', '<html');
    tpls::top("Yks/Renderers/excel_top", tpls::STD, "excel");
    tpls::bottom("Yks/Renderers/excel_bottom", tpls::STD, "excel");

  }


  static function build_xls($table_contents, $headers = array(), $styles=""){
    $table_xml = "<table class='table'>";
    if(!$headers) $headers = array_combine($headers = array_keys(current($table_contents)), $headers);

    $table_xml .= "<tr class='line_head'>"; $col_count=0;
    foreach($headers as $col_name)
        $table_xml .= "<th class='col_{$col_name} col_".($col_count++)."'>$col_name</th>";
    $table_xml .="</tr>";

    foreach($table_contents as $line) {
        $str = "<tr class='line_pair'>";
        foreach($headers as $col_key=>$v)
            $str .="<td>{$line[$col_key]}</td>";
        $str .= "</tr>";
        $table_xml .= $str;
    }
    $table_xml .="</table>";

    $xml_contents = "<body xmlns:xls='excel'><xls:style>$styles</xls:style>$table_xml</body>";

    $doc = new DOMDocument('1.0','UTF-8');
    $tmp = $doc->loadXML($xml_contents, LIBXML_YKS);
    $excel = RSRCS_PATH."/xsl/specials/excel.xsl";
    $doc   = xsl::resolve($doc, $excel);
    $contents = $doc->saveXML();
    $contents = strstr($contents, '<html');
    return $contents;
  }


}