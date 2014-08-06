<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" version="1.0">
  <xsl:template match="icon">
    <!-- xsl comment prevent self closing tag -->
    <span class="icon {@theme}"><xsl:comment/></span>
  </xsl:template>
</xsl:stylesheet>
