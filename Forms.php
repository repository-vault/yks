<?


class Forms {

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
    foreach($element->getElements("input[type!=file], select, textarea") as $el){
        if(!$el['name'] || $el['disabled']) continue;
        if(strtolower($el->getName()) == 'select') $value = array_extract($el->getSelected(), "value");
        elseif(strtolower($el->getName()) == 'textarea') $value = $el->get("innerHTML");
        elseif(in_array($el['type'], array('radio', 'checkbox')) && !$el['checked'])
            $value = $el['value']?$el['value']:"on";
        else $value = (string) $el['value'];

        $queryString [(string) $el['name']]  = $value;
    } return $queryString;
  }


}