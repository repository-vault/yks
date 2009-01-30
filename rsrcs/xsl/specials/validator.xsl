<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml">


<xsl:output method="xml" cdata-section-elements="cdata" version="1.0"  encoding="utf-8" omit-xml-declaration="yes"  doctype-public="-//W3C//DTD XHTML 1.1//EN" doctype-system=" "/>



  <xsl:template match="a | abbr | acronym | address | area  | b| base | bdo | big | blockquote | body | br | caption | cite | code | col | colgroup | dd | del | dfn | div | dl | dt | em | fieldset | form | h1 | h2 | h3 | h4 | h5 | h6 | head | hr | html | i |img |iframe | input | ins | kbd | label | legend | li | link | map | meta | noscript | object | ol | optgroup | option | p | param | pre | q | samp | select | small | span | strong |  sub | sup | table | tbody | td | textarea | tfoot | th | thead | tr | tt | ul  | var | cdata | font " >
 <xsl:element name="{name()}" namespace="http://www.w3.org/1999/xhtml">
    <xsl:apply-templates/>
  </xsl:element>
  </xsl:template> 


<xsl:template match="img">
<img>
<xsl:attribute name="alt">image</xsl:attribute>
<xsl:copy-of select="@src"/>
</img>
</xsl:template>


<xsl:template match="a">
<a><xsl:copy-of select="@href"/><xsl:apply-templates/></a>
</xsl:template>



  <xsl:template match="head/title|b">
 <xsl:element name="{name()}">
    <xsl:apply-templates/>
  </xsl:element>
  </xsl:template>


<xsl:template match="title"><xsl:apply-templates/></xsl:template>
<xsl:template match="box"><div><xsl:apply-templates/></div></xsl:template>

  <xsl:template match='flash'>
	<object type="application/x-shockwave-flash" data="{@src}">
		<xsl:copy-of select="@id|@style"/>
		<param name="movie" value="{@src}"/>
		<param name="quality" value="high" />
		<p><a href='http://www.macromedia.com/go/getflashplayer'>Get macromedia</a></p>
	</object>
  </xsl:template>

  <xsl:template match='empty|style'/>
  <xsl:template match='null|u'><xsl:apply-templates select="node()"/></xsl:template>



</xsl:stylesheet>