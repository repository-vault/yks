<?php


define('XML_STANDALONE', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>');

Class xml_to_xlsx {


  private $data_xml;
  private $workbook_xml;
  private $workbook_rels_xml;
  private $content_types_xml;
  private $doc_props_xml;
  private $worksheet_list_xml = array();
  private $sharedstring_xml;
  private $excel_dir;
  private $nb_worksheet;
  private $nb_relationship;
  private $next_id_shared_string = 0;
  private $shared_string_list = array();

  private $styles;
  const workbook_rels_file = 'xl/_rels/workbook.xml.rels';
  const shared_string_file = 'xl/sharedStrings.xml';
  const workbook_file      = 'xl/workbook.xml';
  const style_file         = 'xl/styles.xml';
  const content_types_file = '[Content_Types].xml';
  const doc_props          = 'docProps/core.xml';

  const worksheet_rels_path= 'worksheets/';
  const worksheet_path     = 'xl/worksheets/';

  const pattern_sheet_xml  = 'xl/worksheets/sheet%s.xml';
  const URI_RELATIONSHIP   = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
  const URI_SPREADSHEET    = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
  const URI_MARKUPCOMP     = 'http://schemas.openxmlformats.org/markup-compatibility/2006';
  const URI_WORKSHEET      = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet';

  const URI_TYPE_RELATIONSHIP = 'http://schemas.openxmlformats.org/package/2006/relationships';
  const URI_TYPE_THEME   = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme';
  const URI_TYPE_STRING  = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings';
  const URI_TYPE_STYLE   = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles';
  const URI_X14AC        = "http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac";

  function __construct($input){
    $this->excel_dir         = RSRCS_PATH."/xslx_zipbase";

    if(gettype($input) == "object") {
      $class = get_class($input);
      if($class == 'SimpleXMLElement')
        $this->data_xml = $input;
      elseif($class == 'DOMDocument')
        $this->data_xml = simplexml_import_dom($input);
      else throw new Exception("Invalid class type $class");

    } elseif(is_file($input))
      $this->data_xml = simplexml_load_file($input);
    elseif(is_string($input))
      $this->data_xml = simplexml_load_string($input);
    else throw new Exception("Invalid class type $class");




    $this->workbook_xml      = self::doc("workbook", array('r' => self::URI_RELATIONSHIP));
      $this->workbook_xml->addChild("sheets");

    $this->workbook_rels_xml = self::doc("Relationships", null, self::URI_TYPE_RELATIONSHIP);
      $this->add_rel(self::URI_TYPE_THEME, "theme/theme1.xml");
      $this->add_rel(self::URI_TYPE_STRING, "sharedStrings.xml");
      $this->add_rel(self::URI_TYPE_STYLE, "styles.xml");


    $this->sharedstring_xml  = self::doc("sst");

    $this->content_types_xml = simplexml_load_file($this->excel_dir.DIRECTORY_SEPARATOR.self::content_types_file);
    $this->doc_props_xml     = simplexml_load_file($this->excel_dir.DIRECTORY_SEPARATOR.self::doc_props);


    $this->styles          = new xlsx_style((string)$this->data_xml->style);

  }

  public function create(){

    $dateTime = new DateTime("@{$_SERVER['REQUEST_TIME']}");
    $date = $dateTime->format(DateTime::W3C);

    $this->doc_props_xml->created = $date;

    foreach($this->data_xml->Worksheet as $worksheet)
      $this->create_sheet_xml($worksheet);

  }

  public function set_creator($creator = 'Anonymous'){
    $this->doc_props_xml->creator = $creator;
  }

  private function add_rel($type, $sheet_path){
    $this->nb_relationship++;
    $rid = "rId{$this->nb_relationship}";
    $relationship = $this->workbook_rels_xml->addChild('Relationship');
    $relationship->addAttribute("Type", $type);
    $relationship->addAttribute('Id', $rid);
    $relationship->addAttribute('Target', $sheet_path);
    return $rid;
  }


  private function create_sheet_xml($worksheet){
    $this->add_ref_worksheet($worksheet['Name']);

    $new_sheet = $this->create_sheet_xml_head($worksheet);
    $this->feed_sheet_xml_data($new_sheet, $worksheet);
    $this->close_sheet_xml($new_sheet);

    $this->worksheet_list_xml[$this->nb_worksheet] = $new_sheet;
  }


  function all_sheets_worksheet(){
    $this->add_ref_worksheet("All pages");

    $new_sheet = $this->create_sheet_xml_head($this->data_xml->Worksheet[0]);


    foreach($this->data_xml->Worksheet as $worksheet) {
      $worksheet->Row['break'] = 'break';
      $this->feed_sheet_xml_data($new_sheet, $worksheet);
    }

    $this->close_sheet_xml($new_sheet);
    $this->worksheet_list_xml[$this->nb_worksheet] = $new_sheet;

  }

  private function close_sheet_xml($new_sheet){

    if(!count($new_sheet->mergeCells->children())) {
     $merge_cell = $new_sheet->mergeCells->addChild('mergeCell');
      $merge_cell->addAttribute('ref', 'A1:A1'); //dummy placeholder...
    }
  }

  private function add_ref_worksheet($worksheet_name){

    //en premier le relationship
    $this->nb_worksheet++;
    $this->worksheet_rows[$this->nb_worksheet] = 1;

    $sheet_path = self::worksheet_rels_path.'sheet'.$this->nb_worksheet.'.xml';

    $rid = $this->add_rel(self::URI_WORKSHEET, $sheet_path);

    //puis le workbook
    $sheet = $this->workbook_xml->sheets->addChild('sheet');

    $worksheet_name = files::safe_name($worksheet_name);
    $worksheet_name = txt::truncate($worksheet_name, 31);

    $sheet->addAttribute('name', $worksheet_name);
    $sheet->addAttribute('sheetId', $this->nb_worksheet);
    $sheet->addAttribute('r:id', $rid, self::URI_RELATIONSHIP);


    $content_sheet = $this->content_types_xml->addChild('Override');
    $content_sheet->addAttribute('PartName', '/xl/'.$sheet_path);
    $content_sheet->addAttribute('ContentType', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml');

  }



  private function create_sheet_xml_head($worksheet){
    $new_sheet = self::doc("worksheet", array(
          'r'     => self::URI_RELATIONSHIP,
          'mc'    => self::URI_MARKUPCOMP,
          'x14ac' => self::URI_X14AC,
    ));
    $new_sheet->addAttribute("x:Ignorable", "x14ac", self::URI_MARKUPCOMP);


    if(count($worksheet->Cols) > 0){
      $cols = $worksheet->Cols[0];

      if($cols['freeze'] == 'freeze') {
        $sheet_view = $new_sheet->addChild("sheetViews")->addChild("sheetView");
        $sheet_view->addAttribute("tabSelected", 1);
        $sheet_view->addAttribute("workbookViewId", 0);

        $pane = $sheet_view->addChild("pane");
        $pane->addAttribute("ySplit", 1);
        $pane->addAttribute("topLeftCell", "A2");
        $pane->addAttribute("activePane", "bottomLeft");
        $pane->addAttribute("state", "frozen");

        $selection = $sheet_view->addChild("selection");
        $selection->addAttribute("pane", "bottomLeft");
      }

      $new_sheet->addChild('cols');
      $col_nb = 0;
      foreach($cols->Col as $col){
        $col_nb++; $col_id = pick($col['Id'], $col_nb);
        $xml_col = $new_sheet->cols->addChild('col');
        $xml_col->addAttribute('min', $col_id);
        $xml_col->addAttribute('max', $col_id);
        $xml_col->addAttribute('width', $col['Width']);
        $xml_col->addAttribute('customWidth', 1);
      }
    }


    $new_sheet->addChild('sheetData');
    $new_sheet->addChild('mergeCells'); //fuu ?


    $margins = $new_sheet->addChild('pageMargins');
    foreach(array('left', 'right', 'top', 'bottom', 'header', 'footer') as $side)
      $margins->addAttribute($side, pick((string)$this->data_xml->printsetup->margins[$side], 0));
    $pageSetup = $new_sheet->addChild('pageSetup');
    foreach(array(
      'paperSize'   => 9, //A4
      'orientation' =>pick((string)$this->data_xml->printsetup['orientation'], "portrait"),
      'horizontalDpi' => 0,
      'verticalDpi'   => 0,
    ) as $k=>$v) $pageSetup->addAttribute($k, $v);


    $rowBreaks = $new_sheet->addChild('rowBreaks');


    return $new_sheet;
   }

  private function feed_sheet_xml_data($new_sheet, $worksheet){
    if(!count($worksheet->Row))
      return; // ? throw


    $nb_row = & $this->worksheet_rows[$this->nb_worksheet];

    foreach($worksheet->Row as $row){
      $sheet_row = $new_sheet->sheetData->addChild('row');
      $sheet_row->addAttribute('r', $nb_row);
      $nb_cell = 1;
      if($row['break']) {
        $breaks = (int) $new_sheet->rowBreaks["count"] + 1;
        $new_sheet->rowBreaks["count"] = $breaks;
        $new_sheet->rowBreaks["manualBreakCount"] = $breaks;
        $bk = $new_sheet->rowBreaks->addChild('brk');
        $bk["id"] = $nb_row - 1; $bk["max"] = 16383; $bk["man"] = 1;
      }


      //chr(65) = A
      foreach($row->Cell as $cell){
        $style = null;
        $cell_merge_start = $this->create_col_prefix($nb_cell).$nb_row;

        $excel_cell = $sheet_row->addChild('c');
        $excel_cell->addAttribute('r', $cell_merge_start);
        $nb_cell++;

        if($cell['class']){
          $style = $this->styles->pick($cell['class']);
          $excel_cell->addAttribute("s", $style);
        }

        if($cell){
          if(isset($cell['type']) && $cell['type'] == 'number'){
            $value = $cell;
          }
          else{
            $excel_cell->addAttribute('t','s');
            $value = $this->add_shared($cell, $cell['Type']);
          }

          $excel_cell->addChild('v', $value);
        }

        if($cell['Colspan']){
          $nb_colspan = $cell['Colspan'] - 1;

          for($i = $nb_colspan; $i > 0; $i--){
            $cell_merge_end = $this->create_col_prefix($nb_cell).$nb_row;
            $excel_cell = $sheet_row->addChild('c');
            $excel_cell->addAttribute('r', $cell_merge_end);
            if($style){
              $excel_cell->addAttribute("s", $style);
            }
            $nb_cell++;
          }

          $merge_cell = $new_sheet->mergeCells->addChild('mergeCell');
          $merge_cell->addAttribute('ref', $cell_merge_start.':'.$cell_merge_end);
        }
      }

      $nb_row++;
    }

  }


  private function create_col_prefix($i){
    $b = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $i --;
    $pow = strlen($b);

    $out = "";
    do {
      $n = $i%$pow;
      $out=  $b[$n] . $out;
      $i -= $n;
      $i /= $pow;
      $i --;
    } while($i>=0);

    return $out;
  }

  private function add_shared($value, $type){
    switch($type){
      case 'String':
      default:
        return $this->add_shared_string($value);
    }
  }

  private function add_shared_string($value){
    $value = ''.$value;

    if(isset($this->shared_string_list[$value])){
      return $this->shared_string_list[$value];
    }

    $shared_string = $this->sharedstring_xml->addChild('si');
    $shared_string->addChild('t', htmlspecialchars($value, ENT_COMPAT, 'UTF-8'));
    $this->shared_string_list[$value] = $this->next_id_shared_string;

    return $this->next_id_shared_string++;
  }


  public function save($dest_file){

    $path = files::tmpdir();
    files::delete_dir($path, false);
    files::copy_dir($this->excel_dir, $path);
    $files = files::find($path, "#\.(git|svn)#");
    foreach($files as $file_path) {
      if(is_file($file_path))
          unlink($file_path);
       if(is_dir($file_path))
          files::delete_dir($file_path);
    }

    $this->workbook_xml->asXML($path.DIRECTORY_SEPARATOR.self::workbook_file);
    $this->workbook_rels_xml->asXML($path.DIRECTORY_SEPARATOR.self::workbook_rels_file);
    $this->sharedstring_xml->asXML($path.DIRECTORY_SEPARATOR.self::shared_string_file);
    $this->content_types_xml->asXML($path.DIRECTORY_SEPARATOR.self::content_types_file);
    $this->doc_props_xml->asXML($path.DIRECTORY_SEPARATOR.self::doc_props);

    foreach($this->worksheet_list_xml as $id => $sheet){
      $sheet->asXML($path.DIRECTORY_SEPARATOR.sprintf(self::pattern_sheet_xml, $id));
    }


    $this->styles->output($path.DIRECTORY_SEPARATOR.self::style_file);

    $this->create_archive($dest_file, $path);
    files::delete_dir($path);
  }


  private static function doc($root, $ens = array(), $ns = self::URI_SPREADSHEET) {
    $str = XML_STANDALONE.CRLF;

    $mask = '';
    if(!empty($ens))
      $mask = mask_join(' ',  $ens, 'xmlns:%2$s="%1$s"');

    $str .= "<$root xmlns=\"$ns\" ".$mask."/>";
    return simplexml_load_string($str);
  }


  private function create_archive($file_path, $dir){
    rbx::ok("Create archive on $file_path");
    if(is_file($file_path)) unlink($file_path);
    $zip = new ZipArchive();

    if ($zip->open($file_path, ZIPARCHIVE::CREATE)!==TRUE)
        Throw new Exception("cannot open archive");

    $files_list = files::find($dir);

    foreach($files_list as $file_path){

      if(is_dir($file_path)) continue;
      $local_name = strip_start($file_path, $dir.DIRECTORY_SEPARATOR);
      $zip->addFile($file_path, $local_name);
      rbx::ok("Add $file_path as $local_name");
    }

    $zip->close();
  }

}