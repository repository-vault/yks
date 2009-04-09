<?

class xsl_cache {
  private $doc;
  private $xsl;

  function __construct($doc, $xsl) {
    $this->doc = $doc;
    $this->xsl = $xsl;
  }

  function out($myks_types_url, $external_mode, $engine_name, $transformation_side ) {

    switch($external_mode) {
        case XSL_DOCUMENT : $external_mode = 'XSL_DOCUMENT'; break;
        case XSL_NODE_SET : $external_mode = 'XSL_NODE_SET'; break;
        default           : $external_mode = 'XSL_DOCUMENT'; break;
    }

    $data = compact('engine_name', 'myks_types_url', 'external_mode');

    $this->xsl->setParameter('xsl', $data);

    $out_file = self::out_file($engine_name, $transformation_side);

    $str = $this->xsl->transformToXML($this->doc);
    $str=str_replace(" xmlns:xsl=\"temp\"","",$str); //patch libxsl version>1.1.17
    file_put_contents($out_file,$str);
    return $out_file;
  }

  static function out_file($engine_name, $transformation_side){
    return XSL_CACHE_PATH."/{$engine_name}_{$transformation_side}.xsl";
  }
}

