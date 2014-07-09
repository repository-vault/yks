<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" version="1.0">
  <xsl:template match="flash" name="flash">
    <xsl:param name="src" select="@src"/>
    <xsl:param name="vars" select="@vars"/>
    <xsl:param name="class" select="@class"/>
    <object type="application/x-shockwave-flash" data="{$src}" class="{$class}">
      <xsl:copy-of select="@*[(name()!='src') and (name()!='class')]"/>
      <param name="movie" value="{$src}"/>
      <param name="quality" value="high"/>
      <xsl:element name="param">
        <xsl:attribute name="name">FlashVars</xsl:attribute>
        <xsl:attribute name="value">
          <xsl:for-each select="var">
            <xsl:value-of select="concat(@name,'=',@value,'&amp;')"/>
          </xsl:for-each>
          <xsl:value-of select="$vars"/>
        </xsl:attribute>
      </xsl:element>
      <xsl:if test="node()">
        <xsl:apply-templates select="*[name()!='var']"/>
      </xsl:if>
      <xsl:if test="not(node())">
        <p>
          <a href="http://www.macromedia.com/go/getflashplayer">Get macromedia</a>
        </p>
      </xsl:if>
    </object>
  </xsl:template>
</xsl:stylesheet>
