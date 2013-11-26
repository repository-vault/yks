<?php
  /**
* Word document template
*
* @property string $doc working path
* @property DOMDocument $document_dom document xml
* @property DOMXpath $document_xpath xpath for $document_dom
* @property DOMDocument $contenttype_dom  [Content_Types].xml
* @property DOMXpath $contenttype_xpath xpath for $contenttype_dom
* @property DOMDocument $rel_dom rel document xml (/word/_rels/document.xml.rels)
* @property DOMXpath $rel_xpath xpath for $rel_dom
* @property WorldField[][] $field_list all field in document
* @property WordTemplateTable[] $table_list all table in document
* @property WordTemplateImg[] $img_list all img in document
*/
class WordTemplate {
  protected $doc;
  protected $document_dom;
  protected $document_xpath;
  protected $contenttype_dom;
  protected $contenttype_xpath;
  protected $rel_dom;
  protected $rel_xpath;

  protected $field_list = array();
  protected $table_list = array();
  protected $img_list = array();
  protected $add_content_type =array();

  const CONTENTPATH = '/[Content_Types].xml';
  const DOCPATH = '/word/document.xml';
  const MEDIADIR = '/word/media';
  const RELSPATH = '/word/_rels/document.xml.rels';

  /**
  * Parse for detect fields, picture
  *
  * @param string $doc working path
  * @return WordTemplate
  */
  public function __construct($doc){
    $this->doc = $doc;

    $this->document_dom= new DOMDocument('1.0', 'UTF-8');
    $this->document_dom->load( $this->doc.self::DOCPATH);

    $this->document_xpath = new DOMXPath($this->document_dom);
    $this->document_xpath->registerNamespace('w', WordNS::W);
    $this->document_xpath->registerNamespace('pic', WordNS::PIC);

    $this->detectComplexField();
    $this->detectSimpleField();
    $this->detectImg();
  }

  public static function fieldIsArray($name){
    $info = null;

    //u for utf8
    if(preg_match('#(\w+)\[(\w+)\]#u', $name, $info)){
      return array(
       'table' => $info[1],
       'col' => $info[2]
      );
    }
    else{
      return false;
    }
  }

  /**
  * Add field in list
  *
  * @param WordField $field
  */
  public function addField(WordField $field){
    //if array
    $info = self::fieldIsArray($field->name);
    if($info){
      $table = $info['table'];
      $col = $info['col'];

      //if table exist
      if(!isset($this->table_list[$table])){
        $this->table_list[$table] = new WordTemplateTable($table);
      }

      //add field in table list
      $field->name = $col;
      $this->table_list[$table]->addField($field);
    }
    else{
      //add field in document list
      $this->field_list[$field->name][] = $field;
    }
  }

  /**
  * Return all field in WordTemplate
  *
  * @return WordField[][]
  */
  public function getFieldList(){
    return $this->field_list;
  }

  /**
  * Return all field in WordTemplateTable
  *
  * @return WordTemplateTable[]
  */
  public function getTableList(){
    return $this->table_list;
  }

  /**
  * Return all image in WordTemplate
  * return WordTemplateImg
  */
  public function getImgList(){
    return $this->img_list;
  }

  /**
  * Parse Document for detect image
  * Add new image in img_list
  */
  protected function detectImg(){
    $this->loadRelDom();

    //each drawing having picture
    $img_node_list = $this->document_xpath->query('//w:drawing[descendant::pic:pic]');

    for($i = 0; $i < $img_node_list->length; $i++){
      $img = new WordTemplateImg($img_node_list->item($i));
      $this->img_list[$img->id] = $img;
      $img->detectPath($this->rel_xpath);
    }

    return $this->img_list;
  }


  /**
  * Replace image by another
  *
  * @param array $value_list for each new image key(contentType, name, data)
  */
  public function replaceImg($value_list){
    foreach($value_list as $id => $value){
      $this->addContentType($value['ext'], $value['contentType']);
      $rid = $this->addMedia($value['name'], $value['data']);
      if(isset($this->img_list[$id])){
        $this->img_list[$id]->setValue($rid);
      }
    }
  }

  protected function loadRelDom(){
    if(!$this->rel_dom){
      $this->rel_dom = new DOMDocument('1.0', 'UTF-8');
      $this->rel_dom->load($this->doc.self::RELSPATH);
      $this->rel_xpath = new DOMXPath($this->rel_dom);

      $this->rel_xpath->registerNamespace('ns', WordNS::NS);
    }
  }

  protected function loadContentTypeDom(){
    if(!$this->contenttype_dom){
      $this->contenttype_dom = new DOMDocument('1.0', 'UTF-8');

      $this->contenttype_dom->load($this->doc.self::CONTENTPATH);
      $this->contenttype_xpath = new DOMXPath($this->contenttype_dom);
      $this->contenttype_xpath->registerNamespace('ctype', WordNS::CType);
    }
  }

  /**
  * add a media in document
  *
  * @param string $name
  * @param string $data
  */
  protected function addMedia($name, $data){
    $this->loadRelDom();
    $name = urlencode(strtolower($name));

    if(file_exists($media_path)){
      if(md5($data) != md5_file($media_path)){
        $i = 0;
        $new_name = $name.'_'.$i;
        while(file_exists($this->doc.self::MEDIADIR.$new_name)){
          $i++;
          $new_name = $name.'_'.$i;
        }
        $name = $new_name;
      }
      else{
        $el = $this->rel_xpath->query("//ns:Relationship[@Target='".self::MEDIADIR.'/'.$new_name."']");
        if($el->length == 1){
          return $el->item(0)->getAttribute('Id');
        }
      }
    }

    $media_path = $this->doc.self::MEDIADIR.'/'.$name;
    file_put_contents($media_path, $data);

    $rel_list = $this->rel_dom->getElementsByTagName('Relationship');
    $nb = $rel_list->length + 1; //already order

    $rid = 'rId'.$nb;
    $el = $this->rel_dom->createElement('Relationship');
    $el->setAttribute('Id', 'rId'.$nb);
    $el->setAttribute('Type', WordNS::IMAGE);
    $el->setAttribute('Target', 'media/'.$name);

    $this->rel_dom->documentElement->appendChild($el);

    return $rid;

  }

  /**
  * Add new content type in [contentTypes].xml
  *
  * @param $ext file extension
  * @parma $contentType
  */
  protected function addContentType($ext, $contentType){
    $this->loadContentTypeDom();

    $result = $this->contenttype_xpath->query("//ctype:Default[@Extension='".$ext."']");

    if(!in_array($ext, $this->add_content_type) && $result->length == 0){
      $el = $this->contenttype_dom->createElement('Default');
      $el->setAttribute('Extension', $ext);
      $el->setAttribute('ContentType', $contentType);
      $this->contenttype_dom->documentElement->appendChild($el);
      $this->add_content_type [] = $ext;
    }
  }

  /**
  * Set value from array
  *
  * @param array $value_list (fiel_name => value)
  */
  public function setFieldValue($value_list){
    //for table
    foreach($this->table_list as $table){
      if(isset($value_list[$table->name]))
        $table->setValue($this->document_dom, $value_list[$table->name]);
      else
        $table->removeRow();
    }


    //for fields
    foreach($this->field_list as $key => $fields){
      foreach($fields as $field){
        if(isset($value_list[$field->name])){
         $value = $value_list[$field->name];
          $field->setValue($this->document_dom, $value);
        }
        else{
          continue;
        }
      }
    }
  }

  /**
  * Save all dom
  *
  */
  public function save(){

    $this->document_dom->save($this->doc.self::DOCPATH);

    if($this->rel_dom)
      $this->rel_dom->save($this->doc.self::RELSPATH);

    if($this->contenttype_dom)
      $this->contenttype_dom->save($this->doc.self::CONTENTPATH);
  }

  /**
  * Detect complex field in dom document (word/document.xml)
  *
  * @param DOMDocument $dom
  * @return complexWordField[]
  */
  protected function detectComplexField(){
    $wts = $this->document_dom->getElementsByTagNameNS(WordNS::W, 'fldChar');

    $field_list = array();

    for( $x = 0; $x < $wts->length; $x++ ) {
      if( $wts->item( $x )->attributes->item(0)->nodeName == 'w:fldCharType' && $wts->item( $x )->attributes->item(0)->nodeValue == 'begin' ) {
        $begin = $x;

        if( $wts->item( $begin )->parentNode->nextSibling ) {
          $brute_name = $wts->item( $begin )->parentNode->nextSibling->childNodes->item(1)->nodeValue;
        } else {
          $begin += 1;
          $brute_name = $wts->item( $begin )->parentNode->previousSibling->childNodes->item(1)->nodeValue;
        }

        $field = WordFieldFactory::createField(WordField::COMPLEX, $brute_name, $wts->item($begin));

        $this->addField($field);
      }
    }
  }

  /**
  * Detect simple field in dom document (word/document.xml)
  *
  * @return SimpleWordField[]
  */
  protected function detectSimpleField(){
    $field_list = array();

    $field_node_list = $this->document_dom->getElementsByTagName('fldSimple');

    for($i = 0; $i < $field_node_list->length; $i++){
      $brute_name = $field_node_list->item($i)->getAttributeNS(WordNS::W, 'instr');

      $field = WordFieldFactory::createField(WordField::SIMPLE, $brute_name, $field_node_list->item($i));
      $this->addField($field);
    }
  }

  public function createArchive($file_path){

    $files_list = files::find($this->doc);

    if(is_file($file_path)) unlink($file_path);
    $zip = new ZipArchive();

    if ($zip->open($file_path, ZIPARCHIVE::CREATE)!==TRUE)
        Throw new Exception("cannot open archive");


    foreach($files_list as $file_path){

      if(is_dir($file_path)) continue;
      $local_name = strip_start($file_path, $this->doc.DIRECTORY_SEPARATOR);
      $zip->addFile($file_path, $local_name);
    }

    $zip->close();
  }

}