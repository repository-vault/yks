<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:ewxsl="http://exslt.org/common">
<xsl:param name="root_xml" select="'mykse'"/>
<xsl:output indent='yes'/>
<xsl:strip-space  elements="*"/>

<xsl:template match='/myks_gen'>
    <xsl:variable name="compiled">
        <xsl:apply-templates select="import"/>
        <xsl:copy-of select="myks/*[name()=$root_xml]"/>

        <xsl:if test="$root_xml='table'">
            <xsl:copy-of select="myks/mykse"/>
        </xsl:if>
    </xsl:variable>
    <myks>
        <xsl:apply-templates select="ewxsl:node-set($compiled)"/>
    </myks>
</xsl:template>

<xsl:template match="import">
    <xsl:copy-of select="document(@src)/myks/*[name()=$root_xml]"/>
</xsl:template>


<xsl:template match="mykse">
  <xsl:variable name="elem" select="./node()[1]"/> <!-- first (and only) child -->
  <xsl:variable name="name"><xsl:value-of select="string(@type)"/></xsl:variable>
  <xsl:variable name="type"><xsl:value-of select="name($elem)"/></xsl:variable>
  <xsl:variable name="alike" select="/mykse[@type=$name]"/>

  <xsl:if test="not(preceding-sibling::mykse[@type=$name])">
   <xsl:element name="{$name}">
    <xsl:attribute name="type"><xsl:value-of select="$type"/></xsl:attribute>

    <xsl:copy-of select="*[name()='mykse']/@type"/>
    <xsl:copy-of select="$elem/@*"/>
    <xsl:copy-of select="$alike/@null|$alike/@birth|$alike/@default"/>
    <xsl:choose>
    <xsl:when test="$type='enum'">
        <xsl:copy-of select="$alike/*/*"/>
    </xsl:when>
    </xsl:choose>

  </xsl:element>
  </xsl:if>
</xsl:template>



<xsl:template match="table">
  <xsl:variable name="name"><xsl:value-of select="string(@name)"/></xsl:variable>
  <xsl:if test="not(preceding-sibling::table[@name=$name])">
  <table><xsl:copy-of select="@*"/>
    <xsl:if test="//mykse[@birth=$name]">
 <xsl:attribute name="birth"><xsl:value-of select="//mykse[@birth=$name]/@type"/></xsl:attribute>
    </xsl:if>
       
    <fields>
         <xsl:copy-of select="//table[@name=$name]/fields/*"/>
    </fields>

    <rules>
        <xsl:copy-of select="//table[@name=$name]/rule"/>
    </rules>

    <grants>
        <xsl:copy-of select="//table[@name=$name]/grant"/>
    </grants>

  </table>
  </xsl:if>
</xsl:template>



<xsl:template match="procedure">
  <procedure><xsl:copy-of select="@*"/>
  <xsl:copy-of select="*"/>
</procedure>
</xsl:template>

<xsl:template match="view">
  <view><xsl:copy-of select="@*"/>
      <xsl:copy-of select="def|rule"/>
      <grants>
        <xsl:copy-of select="grant"/>
      </grants>
  </view>
</xsl:template>



</xsl:stylesheet>
