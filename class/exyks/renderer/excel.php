<?

class exyks_renderer_excel {

  static function process(){
    header(sprintf(HEADER_FILENAME_MASK, yks::$get->config->head->title.".xls")); //filename
    exyks::$headers["excel-server"] = TYPE_CSV;
    exyks::store('XSL_SERVER_PATH', RSRCS_PATH."/xsl/specials/excel.xsl");
    exyks::store('RENDER_SIDE', 'server');
    exyks::store('RENDER_MODE', 'excel');
    exyks::store('RENDER_START', '<html');
    tpls::top("Yks/Renderers/excel_top", tpls::STD, "excel");
    tpls::bottom("Yks/Renderers/excel_bottom", tpls::STD, "excel");

  }


}