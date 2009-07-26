<?

include "XHTMLElement.php";
include "Element.php";
include "Selector.php";


include "Selectors/Utils.php";
include "Selectors/Getters.php";
include "Selectors/Filters.php";
include "Selectors/Pseudo.php";
include "Forms.php";


Element::__register("Forms");


include "functions.php";


$doc = simplexml_load_file("test.htm","Element");
$test= $doc->getElement('body')->getElement('div[id=container] li:last-child');


echo $test->asXML();

//or
echo $test->get('html');
