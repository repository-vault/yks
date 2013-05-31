<?php
/**
* Enum : description for content type in [contentTypes].xml
* @todo : make XML config file??
*/
class WordContentType {

  public static function GetExt($contentType){
    switch($contentType) {
      case 'image/jpeg':
        return 'jpeg';
        break;
      case 'image/bmp' :
        return 'bmp';
      case 'image/x-emf' :
        return 'emf';
        break;
      case 'image/gif':
        return 'gif';
        break;
      case 'image/x-icon':
        return 'ico';
        break;
      case 'image/x-pcx':
        return 'pcx';
        break;
      case 'image/png':
        return 'png';
        break;
      case 'image/tiff' :
        return 'tiff';
        break;
      default:
        throw new Exception('ContentType does\'nt exists');
        break;
    }
  }


}