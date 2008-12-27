<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml">

<xsl:output method="xml" cdata-section-elements="cdata" version="1.0" encoding="utf-8" omit-xml-declaration="yes" doctype-public="-//W3C//DTD XHTML 1.1//EN" doctype-system=" "/>

<!-- THIS STYLESHEET DOES NOTHING AT ALL, BUT TRANFORM XML INTO XHTML -->


<!-- IE create additionnal closing elements if you try to add (apply-templates) contents on an empty one -->
  <xsl:template match="base | meta | link | hr | br | param | img | area | input | col" >
 <xsl:element name="{name()}">
    <xsl:copy-of select="@*"/>
  </xsl:element>
  </xsl:template>

    <xsl:template match="a | abbr | acronym | address |title |  b |  bdo | big | blockquote | body | caption | cite | code | colgroup | dd | del | dfn | div | dl | dt | em | fieldset | form | h1 | h2 | h3 | h4 | h5 | h6 | head | html | i | iframe | ins | kbd | label | legend | li |  map | noscript | object | ol | optgroup | option | p | pre | q | samp | script | select | small | span | strong | style | sub | sup | table | tbody | td | textarea | tfoot | th | thead | tr | tt | ul |u | var | cdata | font" >
	<xsl:element name="{name()}">
    <xsl:copy-of select="@*"/>
    <xsl:apply-templates />
    </xsl:element>
  </xsl:template> 

  <xsl:template match="/">
	<xsl:apply-templates/>
  </xsl:template>



</xsl:stylesheet>