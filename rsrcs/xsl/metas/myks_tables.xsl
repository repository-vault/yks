<?xml version="1.0"?>
<!DOCTYPE xsl SYSTEM "../../dtds/sql_entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:output indent='no'/>
    <xsl:strip-space  elements="*"/>

  <xsl:template match='/myks'>
	<myks_tables>
	<xsl:apply-templates />
	</myks_tables>
  </xsl:template>


  <xsl:template match="table">
    <xsl:variable name="name" select="string(@name)"/>
    <xsl:element name="{$name}">
    <xsl:if test="//table[@extend=$name]">
        <xsl:attribute name="children">
            <xsl:for-each select="//table[@extend=$name]"><xsl:value-of select="@name"/><xsl:if test="not(position()=last())">,</xsl:if></xsl:for-each>
        </xsl:attribute>
    </xsl:if>

        <xsl:copy-of select="@birth"/>
        <xsl:apply-templates select="fields/field">
        <xsl:with-param name="birth" select="@birth"/>
        </xsl:apply-templates>
    </xsl:element>
  </xsl:template>


  <xsl:template match="field">
    <xsl:param name="birth"/>
    <field>
      <xsl:if test="@key='primary' or @name=$birth or @type=$birth">
        <xsl:attribute name="key">primary</xsl:attribute>
      </xsl:if>
      <xsl:choose>
        <xsl:when test="@name">
            <xsl:copy-of select="@type"/>
            <xsl:value-of select="@name"/>
        </xsl:when>
        <xsl:otherwise><xsl:value-of select="@type"/></xsl:otherwise>
      </xsl:choose>
    </field>
  </xsl:template>


  <xsl:template match="*"/>


</xsl:stylesheet>