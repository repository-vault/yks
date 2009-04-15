<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" >

<xsl:output method="xml" cdata-section-elements="cdata" version="1.0" encoding="utf-8" omit-xml-declaration="yes"/>


  <xsl:template match="/">
<html><head> </head>
<body>
    <xsl:apply-templates select="//table"/>
</body>
</html>
  </xsl:template>

<xsl:template match="table[contains(concat(' ',@class,' '),' table ')]">
<xsl:copy-of select="."/>
</xsl:template>

</xsl:stylesheet>