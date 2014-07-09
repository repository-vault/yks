<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" version="1.0">
  <xsl:template match="silverlight">
    <object type="application/x-silverlight-2" data="data:application/x-silverlight-2,">
      <xsl:copy-of select="@*[name()!='src' and name()!='vars']"/>
      <param value="{@src}" name="source"/>
      <param value="onSilverlightError" name="onError"/>
      <param value="white" name="background"/>
      <param value="3.0.40624.0" name="minRuntimeVersion"/>
      <param value="true" name="autoUpgrade"/>
      <xsl:if test="var">
        <xsl:element name="param">
          <xsl:attribute name="name">initParams</xsl:attribute>
          <xsl:attribute name="value">
            <xsl:for-each select="var">
              <xsl:value-of select="concat(@name,'=',@value,',')"/>
            </xsl:for-each>
          </xsl:attribute>
        </xsl:element>
      </xsl:if>
      <xsl:if test="node()">
        <xsl:apply-templates select="*[name()!='var']"/>
      </xsl:if>
    </object>
  </xsl:template>
</xsl:stylesheet>
