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
  
  
  const workbook_rels_file = 'xl/_rels/workbook.xml.rels';
  const shared_string_file = 'xl/sharedStrings.xml';
  const workbook_file      = 'xl/workbook.xml';
  const content_types_file = '[Content_Types].xml';
  
  const worksheet_rels_path= 'worksheets/';
  const worksheet_path     = 'xl/worksheets/';
  
  const sheet_xml          = 'xl/worksheets/sheet1.xml';
  const pattern_sheet_xml  = 'xl/worksheets/sheet%s.xml';
  
  function __construct($excel_dir, $data_xml_file){
    $this->excel_dir         = $excel_dir;
    $this->data_xml          = simplexml_load_file($data_xml_file);
    $this->workbook_xml      = simplexml_load_file($this->excel_dir.self::workbook_file);
    $this->workbook_rels_xml = simplexml_load_file($this->excel_dir.self::workbook_rels_file);
    $this->sharedstring_xml  = simplexml_load_file($this->excel_dir.self::shared_string_file);
    $this->content_types_xml = simplexml_load_file($this->excel_dir.self::content_types_file);
        
    $this->nb_relationship = count($this->workbook_rels_xml->Relationship);
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
    $sheet->addAttribute('name', $name);
    $sheet->addAttribute('sheetId', $this->nb_worksheet);
    $sheet->addAttribute('r:id', $new_rid, 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
    
    
    $content_sheet = $this->content_types_xml->addChild('Override');
    $content_sheet->addAttribute('PartName', '/xl/'.$sheet_path);
    $content_sheet->addAttribute('ContentType', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml');
    
    
    return $this->nb_worksheet;
  }
  
  private function create_sheet_xml($worksheet){
    $new_sheet = simplexml_load_file($this->excel_dir.self::sheet_xml);
    $nb_row = 1;
    
    foreach($worksheet->Row as $row){
      $sheet_row = $new_sheet->sheetData->addChild('row');
      $sheet_row->addAttribute('r', $nb_row);
      $nb_cell = 1;
      
      //chr(65) = A      
      foreach($row->Cell as $cell){
        
        $excel_cell = $sheet_row->addChild('c');
        $excel_cell->addAttribute('r', $this->create_col_prefix($nb_cell).$nb_row);
        $excel_cell->addAttribute('t','s');
        
        if($cell){
          $id_shared_string = $this->add_shared($cell, $cell['Type']);
          $excel_cell->addChild('v', $id_shared_string);
        }
        
        $nb_cell++;
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
        return $this->add_shared_string($value);
    }
  }
  
  private function add_shared_string($value){    
    $value = ''.$value;
        
    if(isset($this->shared_string_list[$value])){
      return $this->shared_string_list[$value];
    }
    
    $shared_string = $this->sharedstring_xml->addChild('si');
    $shared_string->addChild('t', $value);
    $this->shared_string_list[$value] = $this->next_id_shared_string;
    
    return $this->next_id_shared_string++;
  }
  
  public function save($path){
    $path = $path.posix_getpid().'/';
    
    files::delete_dir($path, false);
    files::copy_dir($this->excel_dir, $path);
        
    $this->workbook_xml->asXML($path.self::workbook_file);
    $this->workbook_rels_xml->asXML($path.self::workbook_rels_file);
    $this->sharedstring_xml->asXML($path.self::shared_string_file);
    $this->content_types_xml->asXML($path.self::content_types_file);
        
    foreach($this->worksheet_list_xml as $id => $sheet){
      $sheet->asXML($path.sprintf(self::pattern_sheet_xml, $id));
    }
    
    $this->create_archive($path.'test.xlsx', $path);
  }
  
  private function create_archive($filename, $dir){
    $zip = new ZipArchive();
    
    if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
        Throw new Exception("cannot open archive");
    }
    
    
    $files_list = $this->list_file($dir);
    
    foreach($files_list as $file){      
      $zip->addFile($dir.$file, $file);
      
    }
    
    $zip->close();
  }
  
  private function list_file($dir, $parent = null) {    
    $files_list =  array();
     
    if ($handle = opendir($dir)) {
      while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
          $absolute_path = $dir.$entry;
          if(is_dir($absolute_path))
            $files_list = array_merge($this->list_file($absolute_path.'/', $parent.$entry.'/'), $files_list);
          else
            $files_list[] = $parent.$entry;
        }
      }
      closedir($handle);
    }
    
    return $files_list;
  }
}

$current_dir    = realpath(dirname(__FILE__));
$excel_dir      = $current_dir.'/zipbase/';
$data_xml_file  = $current_dir."/data.xml";


$xlsx = new xml_to_xlsx($excel_dir, $data_xml_file);
$xlsx->create();
$xlsx->save($current_dir.'/tmp/');