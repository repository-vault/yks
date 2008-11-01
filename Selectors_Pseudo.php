<?
class Selectors_Pseudo {

  static function resolve($name, $fallback=false){
    static $parsers = false;if(!$parsers) {
        $parsers = array() ;
        foreach(array_diff(get_class_methods(__CLASS__),array(__FUNCTION__)) as $parser){
            $parsers[str_replace("_","-",$parser)] = $parser;
            $parsers[$parser] = $parser;
        }
    } return $parsers[$name]? $parsers[$name]:($fallback?$name:false);
  }

  static function checked($self){
    return $self['checked'];
  }

  static function not($self, $selector) {
    return !$self->match($selector);
  }

  static function contains($self, $text){
    return strpos($self->get('text'), $text) !==false;
  }

  static function first_child($self){
    return Selectors_Pseudo::index($self, 0);
  }

  static function last_child($self){
    if($self->nextSibling()) return false;
    return true;
  }
  static function only_child($self){
    if($self->previousSibling()) return false;
    if($self->nextSibling()) return false;
    return true;
  }
  static function nth_child($self, $argument, $local){

    if(is_null($argument)) $argument = 'n';
    $parsed = Selectors_Utils::parseNthArgument($argument);
    $special = self::resolve($parsed['special'],true);

    if ($special != 'n'){
        return Selectors_Pseudo::$special($self, $parsed['a'], $local);
    }

    $count = 0;
    while($self = $self->previousSibling()) $count++;

    return ($count % $parsed['a'] == $parsed['b']);
  }

  static function index($self, $index){
    $count = 0;
    while($self = $self->previousSibling()) if(++$count>$index) return false;
    return $count == $index;
  }

  static function even($self, $argument, $local){
    return Selectors_Pseudo::nth_child($self, '2n+1', $local);
  }
  static function odd($self, $argument, $local){
    return Selectors_Pseudo::nth_child($self, '2n', $local);
  }

}

