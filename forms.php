<?php


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

    //mix over ./Element/Element.js, Patchs/Element/Element.js, 
  public static function toQueryString(Element $element){
    $queryString  = array();
    foreach($element->getElements("input, select, textarea") as $el){ //[type!=file], input[type!=submit], input[type!=image]
        $tag = strtolower($el->getName());
        if(!$el['name'] || $el->match("[disabled=disabled]") ) continue;
        if($tag == 'select') {
            $values = $el->getSelected();
            foreach($values as &$v) $v=(string)$v['value'];
            $value = $el->match("[multiple=multiple]") ?  $values : $v;
        } elseif($tag == 'textarea') {
            $value = $el->get("innerHTML");
        } elseif($tag == "input") {
            $type = strtolower($el['type']);
            if(in_array($type, array('file', 'submit', 'image')))
                continue;
            if(in_array($type, array('radio', 'checkbox')) ) {
                if(!$el['checked']) continue;
                $value =  (string) ($el['value']?$el['value']:"on");
            } else $value = (string) $el['value'];
        } else continue;

        $queryString [(string) $el['name']]  = $value;
    } return $queryString;
  }


}
