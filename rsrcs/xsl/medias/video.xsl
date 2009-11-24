<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" version="1.0">
  <xsl:template match="video">
    <xsl:choose>
      <xsl:when test="@format='wmv'">
        <object type="video/x-ms-wmv" data="{@src}">
          <xsl:attribute name="type" mxsl-mode="trident">video/x-ms-wmv</xsl:attribute>
          <xsl:attribute name="type" mxsl-mode="gecko,webkit,presto">application/x-ms-wmp</xsl:attribute>
          <xsl:copy-of select="@id|@style"/>
          <param name="url" value="{@src}"/>
          <param name="src" value="{@src}"/>
          <param name="showcontrols" value="false"/>
          <param name="autoStart" value="true"/>
          <param name="uiMode" value="none"/>
        </object>
      </xsl:when>
      <xsl:otherwise>
        <xsl:call-template name="flash">
          <xsl:with-param name="class" select="'video'"/>
          <xsl:with-param name="src" select="'/?/Yks/ExYks//rsrcs|flvplayer'"/>
<!--/?/Yks/ExYks//rsrcs|skin -->
          <xsl:with-param name="vars" select="concat('flv_path=',@src)"/>
        </xsl:call-template>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template match="audio">
    <xsl:call-template name="flash">
      <xsl:with-param name="class" select="'audio'"/>
      <xsl:with-param name="src" select="'rsrcs/mp3_player.swf'"/>
      <xsl:with-param name="vars" select="concat('mp3=',@src)"/>
    </xsl:call-template>
  </xsl:template>
</xsl:stylesheet>
