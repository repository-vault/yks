<?

class Selectors_Utils {


  static function chk($item, &$uniques){
    if($uniques===false) return true;
    if(!in_array($item, $uniques)) return array_push($uniques, $item);
    return false;
  }

  static function parseSelector($selector){
    $parsed = array(
        'classes'=> array(),
        'pseudos'=> array(),
        'attributes'=>array(),
    );

    preg_match_all(Selectors::$RegExps['combined'], $selector, $ms,PREG_SET_ORDER);
    foreach($ms as $m){ 
        list($cn, $an, $ao, $av, $pn, $pa) = array($m[1], $m[2], $m[3], $m[5], $m[6], $m[7]);
        if($cn) {
            $parsed['classes'][] = $cn;
        } elseif($pn) {
            //todo
        } elseif($an) {
            $parsed['attributes'][] = array(
                'name'=>$an,
                'operator'=>$ao,
                'value'=> $av
            );
        }
    }

    $parsed = array_filter($parsed);
    if(!$parsed) $parsed = null;

    return $parsed;
  }

  static function parseTagAndID($selector){
    $tag = preg_filter(Selectors::$RegExps['tag'], $selector);
    $id  = preg_filter(Selectors::$RegExps['id'], $selector);
    return array($tag?$tag:'*', $id?$id:false);
  }


  static function filter($item, $parsed, $local){
    if($parsed['classes'])
        foreach($parsed['classes'] as $cn)
            if(!Selectors_Filters::byClass($item, $cn))
                return false;
    if($parsed['attributes'])
        foreach($parsed['attributes'] as $att)
            if(!Selectors_Filters::byAttribute($item, $att['name'],  $att['operator'],  $att['value']))
                return false;
    if($parsed['pseudo'])
        return false; //todo

    return true;
  }

  function getByTagAndId($self, $tag, $id){
    if($id) {
        $item = $self->getElementById($id);
        return $item && Selectors_Filters::byTag($item, $tag) ? array($item):array();
    } else {
        return $self->getElementsByTagName($tag);
    }
  }



  static function search($self, $expression, $local){
    $depths = preg_split(Selectors::$RegExps['splitter'], trim($expression), -1, PREG_SPLIT_DELIM_CAPTURE);

    for($depth=0, $depths_nb=count($depths) ; $depth<=$depths_nb ; $depth+=2) {
        $selector = $depths[$depth]; $splitter =  Selectors::$splitters[$depths[$depth-1]]; //PHP
    
        if(!$depth && preg_match(Selectors::$RegExps['quick'], $selector)) { //element rq
            $items = $self->getElementsByTagName($selector);
            continue;
        }
        list($tag, $id) = self::parseTagAndID($selector);

        if($depth == 0) {
            $items = self::getByTagAndId($self, $tag, $id);
        } else {
            $uniques = array(); $found = array();
            foreach($items as $item) $found = Selectors_Getters::$splitter($found, $item, $tag, $id, &$uniques);
            $items  = $found;
        }

        $parsed = Selectors_Utils::parseSelector($selector);
        if($parsed) {
            $filtered = array();
            foreach($items as $item) if (Selectors_Utils::filter($item, $parsed, $local)) $filtered[] = $item;
            $items = $filtered;

        }
    }

    return $items;
  }


}