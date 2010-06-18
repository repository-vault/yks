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
            <xsl:call-template name="button">
              <xsl:with-param name="value" select="@submit"/>
            </xsl:call-template>
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
  <xsl:template match="field" name="field">
    <xsl:param name="depth" select="'0'"/>
    <xsl:param name="item_type" select="string(@type)"/>
    <xsl:param name="mykse" select="$myks_types//*[name()=$item_type]"/>
    <xsl:param name="type" select="$mykse/@type"/>
    <xsl:variable name="elem">
      <xsl:if test="$depth=0">p</xsl:if>
      <xsl:if test="$depth!=0">null</xsl:if>
    </xsl:variable>
    <xsl:element name="{$elem}">
      <xsl:if test="$depth=0">
        <xsl:copy-of select="@id|@class"/>
      </xsl:if>
      <xsl:if test="$depth=0 and @title">
        <span><xsl:value-of select="@title"/> : </span>
      </xsl:if>
      <xsl:variable name="name">
        <xsl:choose>
          <xsl:when test="@name">
            <xsl:value-of select="@name"/>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="@type"/>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:variable>
      <xsl:choose>
        <xsl:when test="not(boolean($item_type))">
          <xsl:apply-templates/>
        </xsl:when>
        <xsl:when test="$item_type and contains(',string,int,time,date,sha1,hidden,',concat(',',$item_type,','))">
          <input type="text" name="{$name}" class="input_{$item_type}">
            <xsl:if test="$item_type='sha1'">
              <xsl:attribute name="type">password</xsl:attribute>
            </xsl:if>
            <xsl:copy-of select="@value|@style|@name|@id|@disabled"/>
          </input>
        </xsl:when>
        <xsl:when test="$item_type='file'">
          <input type="file" name="{$name}">
            <xsl:copy-of select="@style"/>
          </input>
        </xsl:when>
        <xsl:when test="$item_type='upload'">
          <span>
            <xsl:call-template name="button_href">
              <xsl:with-param name="target" select="'upload_file'"/>
              <xsl:with-param name="value" select="@upload_title"/>
            </xsl:call-template>
            <div class="input_{$item_type}" style="display:none" id="{@name}" upload_type="{@upload_type}"/>
          </span>
        </xsl:when>
        <xsl:when test="$type='text'">
          <textarea class="wyzzie" name="{$name}">
            <xsl:copy-of select="@style|@id"/>
            <xsl:apply-templates/>
            <xsl:comment/>
          </textarea>
        </xsl:when>
        <xsl:when test="$item_type='textarea'">
          <textarea name="{$name}">
            <xsl:copy-of select="@style|@id"/>
            <xsl:apply-templates/>
            <xsl:comment/>
          </textarea>
        </xsl:when>
        <xsl:when test="$item_type='bool' and @mode='checkbox'">
          <input type="checkbox" name="{@name}"/>
        </xsl:when>
        <xsl:when test="$type='enum'">
          <xsl:variable name="value" select="@value"/>
          <xsl:choose>
            <xsl:when test="@mode='checkbox' or @mode='radio'">
              <xsl:variable name="mode" select="@mode"/>
              <div class="input_{$mode}s">
                <xsl:for-each select="$mykse/val">
                  <xsl:variable name="val" select="string(.)"/>
                  <xsl:variable name="id" select="concat($name,'_',$val)"/>
                  <div>
                    <input type="{$mode}" value="{$val}" id="{$id}">
                      <xsl:attribute name="name">
                        <xsl:value-of select="$name"/>
                        <xsl:if test="string($mode)='checkbox'">[]</xsl:if>
                      </xsl:attribute>
                      <xsl:if test="contains(@value,$val) or $value=$val">
                        <xsl:attribute name="checked">checked</xsl:attribute>
                      </xsl:if>
                    </input>
                    <label for="{$id}">
                      <xsl:value-of select="$val"/>
                    </label>
                  </div>
                </xsl:for-each>
              </div>
            </xsl:when>
            <xsl:otherwise>
              <select name="{$name}">
                <xsl:copy-of select="@disabled|@multiple"/>
                <xsl:if test="$mykse/@set">
                  <xsl:attribute name="multiple">multiple</xsl:attribute>
                  <xsl:attribute name="name"><xsl:value-of select="$name"/>[]</xsl:attribute>
                </xsl:if>
                <xsl:if test="@null">
                  <option value="">
                    <xsl:value-of select="@null"/>
                  </option>
                </xsl:if>
                <xsl:for-each select="$mykse/val">
                  <xsl:variable name="enum_val" select="string(.)"/>
                  <option value="{$enum_val}">
                    <xsl:if test="contains($value,$enum_val) or $value=$enum_val">
                      <xsl:attribute name="selected">selected</xsl:attribute>
                    </xsl:if>
                    <xsl:value-of select="$enum_val"/>
                  </option>
                </xsl:for-each>
              </select>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:when>
        <xsl:when test="boolean($type)">
          <xsl:call-template name="field">
            <xsl:with-param name="item_type" select="string($type)"/>
            <xsl:with-param name="depth" select="$depth+1"/>
          </xsl:call-template>
        </xsl:when>
      </xsl:choose>
    </xsl:element>
  </xsl:template>
</xsl:stylesheet>
