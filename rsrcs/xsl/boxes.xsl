<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml">

<xsl:template name="box_attribute">
    <xsl:attribute name="class"><xsl:value-of select="@class"/> box <xsl:if test="$jsx"><xsl:if test="contains(@options,'modal')"> modal</xsl:if> <xsl:if test="contains(@options,'fly')"> fly </xsl:if></xsl:if> <xsl:if test="@theme"> <xsl:value-of select="@theme"/>_table </xsl:if></xsl:attribute>
    <xsl:copy-of select="@style|@id|@src"/>

</xsl:template>

  <xsl:template match="box">
    <xsl:variable name="theme" select="@theme"/>

<xsl:choose>

    <xsl:when test="$theme='fieldset'">
        <fieldset>
            <xsl:call-template name="box_attribute"/>
            <xsl:if test="@caption"><legend><xsl:value-of select="@caption"/></legend></xsl:if>
            <xsl:apply-templates/>
        </fieldset>
    </xsl:when>

    <xsl:when test="$theme">
        <table cellspacing='0'>
            <xsl:call-template name="box_attribute"/>

            <tr class='{$theme}_u'>
            <td class='{$theme}_lu'>&#160;</td>
            <td class='{$theme}_mu'><xsl:apply-templates select='mu/node()'/>
                <xsl:if test="@caption"><img src="?/Yks/Scripts/Imgs/titles//box_{@theme}|{@caption}" class="{$theme}_caption" alt="{@caption}"/></xsl:if>
                &#160;</td>
            <td class='{$theme}_ru'>&#160;</td>
          </tr>
          <tr>
            <td class='{$theme}_lm'><xsl:apply-templates select='lm/*'/>&#160;</td>
            <td class='inner {$theme}_mm'>
                <xsl:if test="@id"><div id="{@id}_contents"><xsl:apply-templates/></div></xsl:if>
                <xsl:if test="not(@id)"><xsl:apply-templates/></xsl:if>
            </td>
            <td class='{$theme}_rm'>
            <xsl:if test="contains(@options,'close')">
            <div class='{$theme}_close'>&#160;</div>
            </xsl:if>
            <xsl:if test="contains(@options,'reload')">
            <div class='{$theme}_reload'>&#160;</div>
            </xsl:if>
            <xsl:apply-templates select='rm/*'/>&#160;
            </td>
          </tr>
          <tr>
            <td class='{$theme}_ld'>&#160;</td>
            <td class='{$theme}_md'><xsl:apply-templates select='md/node()'/>&#160;</td>
            <td class='{$theme}_rd'>&#160;</td>
          </tr>
        </table>
    </xsl:when>

    <xsl:otherwise>
        <div>
            <xsl:call-template name="box_attribute"/>
            <xsl:if test="@caption"><p class='title'><xsl:value-of select="@caption"/></p></xsl:if>
            <xsl:if test="not(./node())"><a href="{@src}"><xsl:value-of select="@src"/></a></xsl:if>
            <xsl:if test="@id"><div id="{@id}_contents"><xsl:apply-templates/></div></xsl:if>
            <xsl:if test="not(@id)"><xsl:apply-templates/></xsl:if>
        </div>
    </xsl:otherwise>

</xsl:choose>

  </xsl:template>


<xsl:template match="lm|mu|md|rm|md"/>


</xsl:stylesheet>