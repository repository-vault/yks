<?


class Forms {

  public static function set(Element $element, $data){
    foreach($data  as $name=>$value)
        $element->getElement("*[name='$name']")->addAttribute('value', $value);
    return $element;
  }

    //fast way to retrieve a form element by its submit value
  public static function getFormBySubmit(Element $element, $submit){
    $path = "//form[descendant::input[@type='submit'][@value='$submit']]";
    return current($element->xpath($path));
  }

  public static function getSelected(Element $element){
    return $element->xpath("option[selected]");
  }

  public static function toQueryString(Element $element){
    $queryString  = array();
    foreach($element->getElements("input, select, textarea") as $el){
        if(!$el['name'] || $el['disabled']) continue;
        $value = strtolower($el->tagName) == 'select' 
            ? array_extract($el->getSelected(), "value")
            : (($el['type'] == 'radio' || $el['type'] == 'checkbox') && !$el['checked'])
                ? null : (string) $el['value'];
        $queryString [(string) $el['name']]  = $value;
    } return $queryString;
  }

  public static function submit(Element $element, $sock){
    $data = $element->toQueryString();
print_r($data);
die("WE ARE SUBMITING");

    $sock->request($element['action'], array(), $data);
    return $sock;
  }

}