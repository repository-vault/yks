<?


class Forms {

    //fast way to retrieve a form element by its submit value
  public static function getFormBySubmit(Element $element, $submit){
    $path = "//form[descendant::input[@type='submit'][@value='$submit']]";
    return current($element->xpath($path));
  }

  public static function getSelected(Element $element){
    return $element->getElements("option[selected=selected]:not([disabled=disabled])");
  }

  public static function set(Element $form, $selector, $value){
    $element = $form->getElement($selector);
    $element['value'] = $value;
  }

  public static function toQueryString(Element $element){
    $queryString  = array();
    foreach($element->getElements("input[type!=file], select, textarea") as $el){
        if(!$el['name'] || $el->match("[disabled=disabled]") ) continue;
        if(strtolower($el->getName()) == 'select') {
            $values = $el->getSelected();
            foreach($values as &$v) $v=(string)$v['value'];
            $value = $el->match("[multiple=multiple]") ?  $values : $v;
        } elseif(strtolower($el->getName()) == 'textarea') $value = $el->get("innerHTML");
        elseif(in_array($el['type'], array('radio', 'checkbox')) ) {
            if(!$el['checked']) continue;
            $value =  (string) ($el['value']?$el['value']:"on");
        } else $value = (string) $el['value'];

        $queryString [(string) $el['name']]  = $value;
    } return $queryString;
  }


}
