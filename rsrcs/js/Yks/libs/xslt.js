
function xslt(xsl_lnk){
    this.xsl_xml = xsl_lnk;
    if(Browser.Engine.webkit){ // Safari do not support transformToFragment else
        var liste = this.xsl_xml.getElementsByTagName("output");
    liste.item(0).setAttribute("method", "html");
    }

    this.xsl_xml.resolveExternals = true;
    this.proc = false;
    this.out = function (xml_file){
        if(window.XSLTProcessor){
            if(!this.proc){ //should cache this.proc here
                this.proc = new XSLTProcessor(); 
                this.proc.importStylesheet(this.xsl_xml);
            }
            if(this.proc.transformToFragment)
                return this.proc.transformToFragment(xml_file,document);
        } var IESoup = $n("div"); IESoup.innerHTML=xml_file.transformNode(this.xsl_xml);
        return IESoup;
    }
}