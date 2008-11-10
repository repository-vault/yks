<?

include "XHTMLElement.php";
include "Element.php";
include "Selector.php";
include "functions.php";



$doc = simplexml_load_file("test.htm","Element");
$test= $doc->getElement('body')->getElement('div[id=container] li:last-child');


echo $test->asXML();

//or
echo $test->get('html');
