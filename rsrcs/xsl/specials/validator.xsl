<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" version="1.0">
  <xsl:output method="xml" cdata-section-elements="cdata" version="1.0" encoding="utf-8" omit-xml-declaration="yes" doctype-public="-//W3C//DTD XHTML 1.1//EN" doctype-system="http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"/>
  <xsl:template match="a | abbr | acronym | address | area  | b| base | bdo | big | blockquote | body | br | caption | cite | code | col | colgroup | dd | del | dfn | div | dl | dt | em | fieldset | form | h1 | h2 | h3 | h4 | h5 | h6 | head | hr | html | i |img |iframe | input | ins | kbd | label | legend | li | link | map | meta | noscript | object | ol | optgroup | option | p | param | pre | q | samp | select | small | span | strong |  sub | sup | table | tbody | td | textarea | tfoot | th | thead | tr | tt | ul  | var | cdata | font ">
    <xsl:element name="{name()}">
      <xsl:copy-of select="@*[name()!='lang']"/>
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
    <a>
      <xsl:copy-of select="@href"/>
      <xsl:apply-templates/>
    </a>
  </xsl:template>
  <xsl:template match="head/title|b">
    <xsl:element name="{name()}">
      <xsl:apply-templates/>
    </xsl:element>
  </xsl:template>
  <xsl:template match="title">
    <xsl:apply-templates/>
  </xsl:template>
  <xsl:template match="box">
    <div>
      <xsl:apply-templates/>
    </div>
  </xsl:template>
  <xsl:template match="flash">
    <div>
      <xsl:apply-templates select="*[name()!='var']"/>
    </div>
  </xsl:template>
  <xsl:template match="empty|style|domready|script"/>
  <xsl:template match="null|u">
    <xsl:apply-templates select="node()"/>
  </xsl:template>
</xsl:stylesheet>