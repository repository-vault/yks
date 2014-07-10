<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" version="1.0">
  <xsl:template match="box">
    <xsl:variable name="theme" select="@theme"/>
    <div>
      <xsl:attribute name="class"><xsl:value-of select="@class"/> box <xsl:if test="$jsx"><xsl:if test="contains(@options,'modal')"> modal</xsl:if><xsl:if test="contains(@options,'fly')"> fly </xsl:if></xsl:if><xsl:if test="@theme"><xsl:value-of select="@theme"/>_box </xsl:if></xsl:attribute>
      <xsl:copy-of select="@style|@id|@src|@url"/>
      <xsl:if test="@caption">
        <p class="title">
          <xsl:value-of select="@caption"/>
          <xsl:if test="contains(@options,'close')">
            <span class="box_action {$theme}_close"> </span>
          </xsl:if>
        </p>
      </xsl:if>
      <xsl:if test="not(./node())">
        <a href="{@src}" class="box_default">
          <xsl:value-of select="@src"/>
        </a>
      </xsl:if>
      <xsl:if test="node()">
        <xsl:if test="@id">
          <div id="{@id}_contents">
            <xsl:apply-templates/>
          </div>
        </xsl:if>
        <xsl:if test="not(@id)">
          <xsl:apply-templates/>
        </xsl:if>
      </xsl:if>
    </div>
  </xsl:template>
</xsl:stylesheet>
