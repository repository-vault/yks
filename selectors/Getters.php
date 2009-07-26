<?
class Selectors_Getters {

  static function descendant($found, $self, $tag, $id, &$uniques){ // ' '
    $items = Selectors_Utils::getByTagAndId($self, $tag, $id);
    foreach($items as $item) 
        if( Selectors_Utils::chk($item, $uniques)) $found[] = $item;
    return $found;
  }

  static function child($found, $self, $tag, $id, &$uniques) { // '>'
    $children = Selectors_Utils::getByTagAndId($self, $tag, $id);
    foreach($children as $child)
        if($child->getParent() == $self && Selectors_Utils::chk($child, $uniques))  $found[] = $child;

    return $found;
  }

  static function direct_sibling($found, $self, $tag, $id, &$uniques) { // '+'
    $siblings = $self->getParent()->children(); $next=false;
    foreach($siblings as $sibling){
        if(!$next && ( ($next = $sibling == $self) || true )) continue;
        $self = $sibling;
        if (Selectors_Utils::chk($self, $uniques)
            && Selectors_Filters::byTag($self, $tag)
            && Selectors_Filters::byID($self, $id))  $found[] = $self;
        break;
    }
    return $found;
  }

  static function sibling($found, $self, $tag, $id, &$uniques){ // '~'
    $siblings = $self->getParent()->children(); $next=false;
    foreach($siblings as $sibling){
        if(!$next && ( ($next = $sibling == $self) || true )) continue;
        $self = $sibling;
        if (!Selectors_Utils::chk($self, $uniques)) break;
        if( Selectors_Filters::byTag($self, $tag)
            && Selectors_Filters::byID($self, $id))  $found[] = $self;
    }
    return $found;
  }
}

