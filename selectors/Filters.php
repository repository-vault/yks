<?

class Selectors_Filters {

  static function byTag($self, $tag) {
    return $tag=="*" || strtolower($self->getName()) == $tag;
  }

  static function byID($self, $id) {
    return !$id || $self['id'] && $self['id'] == $id;
  }

  static function byClass($self, $class) {
    return $self['class'] && strpos(" {$self['class']} ", " $class ")!==false;
  }

  static function byPseudo($self, $parser, $argument, $local) {
    $parser = Selectors_Pseudo::resolve($parser);
    return Selectors_Pseudo::$parser($self, $argument, $local);
  }

  static function byAttribute($self, $name, $operator, $value) {
    $result = $self[$name];
    if(!$result) return ( $operator  == '!=' );
    if(!$operator || $value == null ) return true;
    switch($operator) {
      case '=' : return ($result == $value );
      case '*=': return (strpos($result, $value) != false);
      case '^=': return (substr($result,0,strlen($value)) == $value);
      case '$=': return (substr($result,-strlen($value)) == $value);
      case '!=': return ($result != $value);
      case '~=': return (strpos(" $result ", " $value ") != false);
      case '|=': return (strpos("-$result-", "-$value-") != false);
    }
    return false;
  }

}
