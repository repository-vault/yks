var XML = {
 serialize:function(el){
    if(window.XMLSerializer)
        return (new XMLSerializer()).serializeToString(el);
    return el.xml;
  },
  makesoup:function(xml_str){
   if(xml_str.startsWith("<!DOCTYPE"))
      xml_str = xml_str.replace(/^<!DOCTYPE[^>]+>/, "");
   return $n("div").set('html', xml_str);//.getFirst(); //dont <null><br/><box/></null>
  }
};


function transformer_xslt(xsl_lnk){
    this.xsl_xml = xsl_lnk;
    if(Browser.Engine.webkit){ // Safari do not support transformToFragment else
        var liste = this.xsl_xml.getElementsByTagName("output");
    liste.item(0).setAttribute("method", "html");
    }

    this.xsl_xml.resolveExternals = true;
    this.proc = false;
    this.out = function (xml_doc){
        if(window.XSLTProcessor){
            if(!this.proc){ //should cache this.proc here
                this.proc = new XSLTProcessor(); 
                this.proc.importStylesheet(this.xsl_xml);
            }
            if(this.proc.transformToFragment)
                return this.proc.transformToFragment(xml_doc,document);
        } return XML.makesoup(xml_doc.transformNode(this.xsl_xml));
    }
};



function transformer_dummy(){
    this.out = function(xml_doc){
        var str = XML.serialize(xml_doc);
        var ret = XML.makesoup(str);
        return ret;
    }
};

