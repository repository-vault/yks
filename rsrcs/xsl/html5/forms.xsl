<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" version="1.0">
  <xsl:template match="ks_form">
    <form class="ks_form jsx_form {@class}" id="{@ks_action}" method="post" action="{//jsx/@href_ks}">
      <xsl:copy-of select="@onsubmit|@action|@id|@target|@style|@enctype"/>
      <div>
        <input type="hidden" name="ks_flag" value="{//jsx/@ks_flag}"/>
        <input type="hidden" name="ks_action" value="{@ks_action}"/>
        <input type="submit" style="display:none"/>
        <xsl:apply-templates/>
        <xsl:if test="@submit">
          <div class="submit">
            <button>
              <xsl:value-of select="@submit"/>
            </button>
          </div>
        </xsl:if>
      </div>
    </form>
  </xsl:template>
  <xsl:template match="fields">
    <xsl:choose>
      <xsl:when test="@caption">
        <fieldset>
          <xsl:copy-of select="@id|@class|@style"/>
          <legend>
            <xsl:value-of select="@caption"/>
          </legend>
          <xsl:apply-templates/>
        </fieldset>
      </xsl:when>
      <xsl:otherwise>
        <div>
          <xsl:copy-of select="@id|@class|@style"/>
          <xsl:apply-templates/>
        </div>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
</xsl:stylesheet>
