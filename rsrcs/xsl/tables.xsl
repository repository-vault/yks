<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml">

  <xsl:template match="tfail">
    <tr class='line_fail'><xsl:copy-of select="@*"/>
    <td>
    <xsl:attribute name="colspan">
        <xsl:value-of select="count(ancestor::table//tr[1]/th)"/>
    </xsl:attribute>
        <xsl:apply-templates/>
    </td></tr>
  </xsl:template>

  <xsl:template match="tr[contains(@class, 'line_pair')]">

<xsl:variable name="pair">
    <xsl:choose>
        <xsl:when test="position() mod 2=0">odd</xsl:when>
        <xsl:otherwise>even</xsl:otherwise>
    </xsl:choose>
</xsl:variable>
    <tr class="{@class} line_{$pair}"><xsl:copy-of select="@*[name()!='class']"/><xsl:apply-templates/></tr>


  </xsl:template>


</xsl:stylesheet>