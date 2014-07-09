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

</xsl:stylesheet>