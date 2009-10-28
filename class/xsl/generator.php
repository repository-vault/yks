<?php

//ex/yks xsl cache

class xsl_cache {
  private $doc;
  private $xsl;
  private $params = array();

  function __construct($doc, $xsl) {
    $this->doc = $doc;
    $this->xsl = $xsl;
  }

  function parameters_add($params){
    $this->params = array_merge($this->params, $params);
  }

  function out($engine_name, $rendering_side) {
    $this->parameters_add(compact('engine_name', 'rendering_side'));

    $this->xsl->setParameter('xsl', $this->params );
    $out_file = self::out_file($engine_name, $rendering_side);

    $str = $this->xsl->transformToXML($this->doc);
    $str=str_replace(" xmlns:xsl=\"temp\"","",$str); //patch libxsl version>1.1.17
    file_put_contents($out_file,$str);
    return $out_file;
  }

  static function out_file($engine_name, $rendering_side){
    return XSL_CACHE_PATH."/{$engine_name}_{$rendering_side}.xsl";
  }
}

