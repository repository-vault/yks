
function dom_load_string(str){
  if (window.ActiveXObject){
		var doc=new ActiveXObject("Microsoft.XMLDOM");
		doc.async="false";doc.loadXML(str);
  } else {
	var parser=new DOMParser();
	var doc=parser.parseFromString(str,"text/xml");
  } return doc;
}

	//this was inspired by tgay
function evalscript(content){
	if (content) (window.execScript) ? window.execScript(content) : window.setTimeout(content, 0);
}
