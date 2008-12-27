<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml">


  <xsl:template match="a[@ext]">
    <a>
        <xsl:copy-of select="@*"/>
        <xsl:attribute name="href"><xsl:value-of select="@ext"/></xsl:attribute>
        <xsl:attribute name="class"><xsl:value-of select="@class"/> ext</xsl:attribute>
        <xsl:apply-templates/>
    </a>

  </xsl:template>


</xsl:stylesheet>