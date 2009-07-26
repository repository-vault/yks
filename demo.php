<?

include "xhtmlelement.php";
include "element.php";
include "selector.php";


include "selectors/utils.php";
include "selectors/getters.php";
include "selectors/filters.php";
include "selectors/pseudo.php";
include "forms.php";


Element::__register("Forms");


include "functions.php";


$doc = simplexml_load_file("test.htm","Element");
$test= $doc->getElement('body')->getElement('div[id=container] li:last-child');


echo $test->asXML();

//or
echo $test->get('html');
