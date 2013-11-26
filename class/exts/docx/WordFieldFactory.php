<?php
  /**
* Factory for WordField
*/
class WordFieldFactory {

  /**
  * Return an instance of WordField
  *
  * @param string $type
  * @param string $brute_name
  * @param DOMElement $begin
  * @return WordField
  */
  static function createField($type, $brute_name, $begin){
    switch ($type) {
      case WordField::COMPLEX:
        return new WordFieldComplex($brute_name, $begin);
        break;
      case WordField::SIMPLE:
        return new WordFieldSimple($brute_name, $begin);
        break;
      default:
        Throw new Exception("Type doesn't exist");
        break;
    }
  }
}