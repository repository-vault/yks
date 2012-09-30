<?

Class xml_to_xlsx {


  private $data_xml;
  private $workbook_xml;
  private $workbook_rels_xml;
  private $content_types_xml;
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
  
  const worksheet_rels_path= 'worksheets/';
  const worksheet_path     = 'xl/worksheets/';
  
  const pattern_sheet_xml  = 'xl/worksheets/sheet%s.xml';
  const URI_RELATIONSHIP   = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

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
    
    $this->workbook_xml      = simplexml_load_file($this->excel_dir.DIRECTORY_SEPARATOR.self::workbook_file);
    $this->workbook_rels_xml = simplexml_load_file($this->excel_dir.DIRECTORY_SEPARATOR.self::workbook_rels_file);
    $this->sharedstring_xml  = simplexml_load_file($this->excel_dir.DIRECTORY_SEPARATOR.self::shared_string_file);
    $this->content_types_xml = simplexml_load_file($this->excel_dir.DIRECTORY_SEPARATOR.self::content_types_file);
        
    $this->nb_relationship = count($this->workbook_rels_xml->Relationship);
    $this->styles           = new xlsx_style((string)$this->data_xml->style);

  }
  
  public function create(){
    foreach($this->data_xml->Worksheet as $worksheet){
  
      //on ajoute la feuille
      $name = $worksheet['Name'];
      
      $this->add_ref_worksheet($name);
      $this->create_sheet_xml($worksheet);
    }
  }
  
  private function add_ref_worksheet($name){
    
    //en premier le relationship
    $this->nb_worksheet++;
    
    $new_rid = 'rId'.($this->nb_relationship+$this->nb_worksheet);
    
    $relationship = $this->workbook_rels_xml->addChild('Relationship');
    
    $sheet_path = self::worksheet_rels_path.'sheet'.$this->nb_worksheet.'.xml';
    
    $relationship->addAttribute("Type", 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet');
    $relationship->addAttribute('Id', $new_rid);
    $relationship->addAttribute('Target', $sheet_path);
        
    //puis le workbook
    $sheet = $this->workbook_xml->sheets->addChild('sheet');

    $worksheet_name = $name;
    $worksheet_name = files::safe_name($worksheet_name);
    $worksheet_name = txt::truncate($worksheet_name, 31);

    $sheet->addAttribute('name', $worksheet_name);
    $sheet->addAttribute('sheetId', $this->nb_worksheet);
    $sheet->addAttribute('r:id', $new_rid, self::URI_RELATIONSHIP);
    
    
    $content_sheet = $this->content_types_xml->addChild('Override');
    $content_sheet->addAttribute('PartName', '/xl/'.$sheet_path);
    $content_sheet->addAttribute('ContentType', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml');
    
    
    return $this->nb_worksheet;
  }
  
  private function create_sheet_xml($worksheet){
    $new_sheet = simplexml_load_string(
          '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.CRLF.
          '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" 
                      xmlns:r="'.self::URI_RELATIONSHIP.'"
                      xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"
                      xmlns:x14ac="http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac"
                      mc:Ignorable="x14ac"
          />' );
    $nb_row = 1;
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
    
    if(count($worksheet->Row))
      $new_sheet->addChild('sheetData');
    
    foreach($worksheet->Row as $row){
      $sheet_row = $new_sheet->sheetData->addChild('row');
      $sheet_row->addAttribute('r', $nb_row);
      $nb_cell = 1;
      
      //chr(65) = A      
      foreach($row->Cell as $cell){
        $style = null;
        $cell_merge_start = $this->create_col_prefix($nb_cell).$nb_row;
        
        $excel_cell = $sheet_row->addChild('c');
        $excel_cell->addAttribute('r', $cell_merge_start);
        $excel_cell->addAttribute('t','s');
        $nb_cell++;
        
        if(  $cell['class']){
          $style = $this->styles->pick($cell['class']);
          $excel_cell->addAttribute("s", $style);
        }
        
        if($cell){
          $id_shared_string = $this->add_shared($cell, $cell['Type']);
          $excel_cell->addChild('v', $id_shared_string);            
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
          
          if(!$new_sheet->mergeCells)
            $new_sheet->addChild('mergeCells');
          
          $merge_cell = $new_sheet->mergeCells->addChild('mergeCell');
          $merge_cell->addAttribute('ref', $cell_merge_start.':'.$cell_merge_end);
        }
      }
      
      $nb_row++;
    }    
    
    $this->worksheet_list_xml[$this->nb_worksheet] = $new_sheet;
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
        
    foreach($this->worksheet_list_xml as $id => $sheet){
      $sheet->asXML($path.DIRECTORY_SEPARATOR.sprintf(self::pattern_sheet_xml, $id));
    }


    $this->styles->output($path.DIRECTORY_SEPARATOR.self::style_file);
    
    $this->create_archive($dest_file, $path);
    files::delete_dir($path);
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