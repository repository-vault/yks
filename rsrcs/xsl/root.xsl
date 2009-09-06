<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:import href="html.xsl"/>
  <xsl:import href="boxes.xsl"/>
  <xsl:import href="buttons.xsl"/>
  <xsl:import href="forms.xsl"/>
  <xsl:import href="tables.xsl"/>
  <xsl:import href="links.xsl"/>
  <xsl:import href="toggle.xsl"/>
  <xsl:import href="medias/flash.xsl"/>
  <xsl:import href="medias/video.xsl"/>
  <xsl:output method="xml" cdata-section-elements="cdata" version="1.0" encoding="utf-8" omit-xml-declaration="yes" doctype-public="-//W3C//DTD XHTML 1.1//EN" doctype-system=" " mxsl-mode="trident" mxsl-side="client"/>
  <xsl:output method="xml" cdata-section-elements="cdata script" version="1.0" encoding="utf-8" omit-xml-declaration="yes" doctype-public="-//W3C//DTD XHTML 1.1//EN" doctype-system=" " mxsl-mode="webkit,gecko,presto" mxsl-side="client"/>
  <xsl:output method="xml" cdata-section-elements="cdata" version="1.0" encoding="utf-8" omit-xml-declaration="yes" doctype-public="-//W3C//DTD XHTML 1.1//EN" doctype-system=" " mxsl-side="server"/>
  <xsl:variable name="jsx" select="/*/@jsx"/>
  <xsl:template match="/">
    <xsl:apply-templates/>
  </xsl:template>
</xsl:stylesheet>
