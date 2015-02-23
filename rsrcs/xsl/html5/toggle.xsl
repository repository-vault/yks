<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml">

  <xsl:template match="toggler">
    <div class='toggle_zone {@class}'>
        <div class='toggle_anchor'><xsl:value-of select="@caption"/></div>
        <xsl:apply-templates/>
    </div>
  </xsl:template>


</xsl:stylesheet>