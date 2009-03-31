<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml">



 <xsl:template match="button" name="button">
	<xsl:param name="href" select="@href"/>
	<xsl:param name="target" select="@target"/>
	<xsl:param name="value" select="."/>
	<xsl:param name="confirm" select="@confirm"/>
	<xsl:if test="$href">
	<a href="{$href}" target="{$target}">
	  <xsl:copy-of select="@onclick|@style|@class"/>
	  <img src="?/Yks/Scripts/Imgs/titles//{@theme}|{$value}" alt="{$value}">
            <xsl:copy-of select="@src"/>
          </img>
	</a>
	</xsl:if>
	<xsl:if test="not($href)">
	  <input alt="{$value}" type="image" src="?/Yks/Scripts/Imgs/titles//{@theme}|{$value}">
        <xsl:if test="$confirm"><xsl:attribute name="onclick">return window.confirm("<xsl:value-of select="$confirm"/> ?")</xsl:attribute></xsl:if>
            <xsl:copy-of select="@name|@src|@class|@onclick|@id|@style"/>
            </input>
	</xsl:if>
  </xsl:template>

</xsl:stylesheet>
