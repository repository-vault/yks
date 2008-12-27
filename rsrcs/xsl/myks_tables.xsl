<?xml version="1.0"?>
<!DOCTYPE xsl SYSTEM "../dtds/sql_entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:output indent='no'/>
    <xsl:strip-space  elements="*"/>

  <xsl:template match='/myks'>
	<myks_tables>
	<xsl:apply-templates />
	</myks_tables>
  </xsl:template>


  <xsl:template match="table">
    <xsl:element name="{@name}">
        <xsl:copy-of select="@birth"/>
        <xsl:apply-templates select="fields/field"/>
    </xsl:element>
  </xsl:template>


  <xsl:template match="field">
    <field><xsl:choose>
        <xsl:when test="@name">
            <xsl:copy-of select="@type"/>
            <xsl:value-of select="@name"/>
        </xsl:when>
        <xsl:otherwise><xsl:value-of select="@type"/></xsl:otherwise>
    </xsl:choose></field>
  </xsl:template>


  <xsl:template match="*"/>


</xsl:stylesheet>
