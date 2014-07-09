<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" version="1.0">
  <xsl:template name="box_attribute">
    <xsl:attribute name="class"><xsl:value-of select="@class"/> box <xsl:if test="$jsx"><xsl:if test="contains(@options,'modal')"> modal</xsl:if><xsl:if test="contains(@options,'fly')"> fly </xsl:if></xsl:if> <xsl:if test="@theme"><xsl:value-of select="@theme"/>_table <xsl:value-of select="@theme"/>_box </xsl:if></xsl:attribute>
    <xsl:copy-of select="@style|@id|@src|@url"/>
  </xsl:template>
  <xsl:template match="box">
    <xsl:variable name="theme" select="@theme"/>
    <xsl:choose>
      <xsl:when test="$theme='fieldset'">
        <fieldset>
          <xsl:call-template name="box_attribute"/>
          <xsl:if test="@caption">
            <legend>
              <xsl:value-of select="@caption"/>
            </legend>
          </xsl:if>
          <xsl:apply-templates/>
        </fieldset>
      </xsl:when>
      <xsl:when test="$theme">
        <table cellspacing="0">
          <xsl:call-template name="box_attribute"/>
          <tr class="{$theme}_u">
            <td class="{$theme}_lu"> </td>
            <td class="{$theme}_mu"><xsl:if test="@caption"><img src="?/Yks/Scripts/Imgs/titles//box_{@theme}|{@caption}" class="{$theme}_caption" alt="{@caption}"/></xsl:if><xsl:if test="mu/node()"><div class="{$theme}_mu_contents"><xsl:apply-templates select="mu/node()"/></div></xsl:if>
                 </td>
            <td class="{$theme}_ru"> </td>
          </tr>
          <tr>
            <td class="{$theme}_lm"><xsl:apply-templates select="lm/*"/> </td>
            <td class="inner {$theme}_mm">
              <xsl:if test="@id">
                <div id="{@id}_contents">
                  <xsl:apply-templates/>
                </div>
              </xsl:if>
              <xsl:if test="not(@id)">
                <xsl:apply-templates/>
              </xsl:if>
            </td>
            <td class="{$theme}_rm"><xsl:if test="contains(@options,'close')"><div class="box_action {$theme}_close"> </div></xsl:if><xsl:if test="contains(@options,'reload')"><div class="box_action {$theme}_reload"> </div></xsl:if><xsl:apply-templates select="rm/*"/> 
            </td>
          </tr>
          <tr class="{$theme}_d">
            <td class="{$theme}_ld"> </td>
            <td class="{$theme}_md"><xsl:apply-templates select="md/node()"/> </td>
            <td class="{$theme}_rd"><xsl:if test="contains(@options,'resize')"><div class="box_action {$theme}_resize"> </div></xsl:if><xsl:apply-templates select="rd/node()"/> </td>
          </tr>
        </table>
      </xsl:when>
      <xsl:otherwise>
        <div>
          <xsl:call-template name="box_attribute"/>
          <xsl:if test="@caption">
            <p class="title">
              <xsl:value-of select="@caption"/>
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
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template match="lm|mu|md|rm|md|rd"/>
</xsl:stylesheet>
