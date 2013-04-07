<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:output indent="no"/>
  <xsl:strip-space elements="*"/>
  <xsl:template match="/myks">
    <myks_tables>
      <xsl:apply-templates/>
    </myks_tables>
  </xsl:template>
  <xsl:template match="table">
    <xsl:variable name="name" select="string(@name)"/>
    <xsl:element name="{$name}">
      <xsl:copy-of select="@birth"/>
      <xsl:apply-templates select="fields/field">
        <xsl:with-param name="birth" select="@birth"/>
      </xsl:apply-templates>
      <xsl:if test="//table[@extend=$name]">
        <xsl:for-each select="//table[@extend=$name]">
          <xsl:element name="child">
            <xsl:value-of select="@name"/>
          </xsl:element>
        </xsl:for-each>
      </xsl:if>
    </xsl:element>
  </xsl:template>
  <xsl:template match="field">
    <xsl:param name="birth"/>
    <xsl:variable name="type">
      <xsl:value-of select="@type"/>
    </xsl:variable>
    <field name="{@name}">
      <!--  or @type=$birth -->
      <xsl:copy-of select="@type|@key|@null"/>
      <xsl:if test="string(@name)=$birth">
        <xsl:attribute name="key">primary</xsl:attribute>
      </xsl:if>
      <xsl:if test="//*[name()=$type]/@birth">
        <xsl:copy-of select="@delete"/>
      </xsl:if>
      <!-- please remove this when possible .. -->
      <xsl:value-of select="string(@name)"/>
    </field>
  </xsl:template>
  <xsl:template match="*"/>
</xsl:stylesheet>
