<?xml version="1.0" encoding="utf-8"?>
<wxsl:stylesheet xmlns:wxsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="temp" xmlns:ewxsl="http://exslt.org/common" version="1.0">
  <wxsl:namespace-alias stylesheet-prefix="xsl" result-prefix="wxsl"/>
  <wxsl:strip-space elements="*"/>
  <!-- params -->
  <wxsl:param name="rendering_side" value="client"/>
  <wxsl:param name="engine_name" value="gecko"/>
  <wxsl:param name="mykse_file_url" value="http//site_url"/>
  <wxsl:param name="mykse_file_path" value="/path/to/file.xsl"/>
  <!-- /params -->
  <wxsl:template match="/wxsl:stylesheet">
    <wxsl:variable name="external_mode">
      <wxsl:choose>
        <wxsl:when test="$rendering_side = 'client' and ( $engine_name = 'gecko' or $engine_name = 'trident')">XSL_DOCUMENT</wxsl:when>
        <wxsl:otherwise>XSL_NODE_SET</wxsl:otherwise>
      </wxsl:choose>
    </wxsl:variable>
    <xsl:stylesheet>
      <wxsl:copy-of select="@*"/>
      <wxsl:if test="$external_mode='XSL_NODE_SET'">
        <wxsl:attribute name="extension-element-prefixes">ewxsl</wxsl:attribute>
      </wxsl:if>
      <wxsl:apply-templates select="wxsl:output|wxsl:variable"/>
      <wxsl:choose>
        <wxsl:when test="$external_mode='XSL_DOCUMENT'">
          <wxsl:element name="xsl:variable">
            <wxsl:attribute name="name">myks_types</wxsl:attribute>
            <wxsl:attribute name="select">document('<wxsl:value-of select="$mykse_file_url"/>')</wxsl:attribute>
          </wxsl:element>
        </wxsl:when>
        <wxsl:when test="$external_mode='XSL_NODE_SET'">
          <wxsl:element name="xsl:variable">
            <wxsl:attribute name="name">myks_types_tree</wxsl:attribute>
            <wxsl:copy-of select="document($mykse_file_path)/*"/>
          </wxsl:element>
          <wxsl:element name="xsl:variable">
            <wxsl:attribute name="name">myks_types</wxsl:attribute>
            <wxsl:attribute name="select">ewxsl:node-set($myks_types_tree)</wxsl:attribute>
          </wxsl:element>
        </wxsl:when>
      </wxsl:choose>
      <wxsl:apply-templates select="wxsl:import"/>
      <wxsl:apply-templates select="wxsl:template"/>
    </xsl:stylesheet>
  </wxsl:template>
  <wxsl:template match="wxsl:import">
    <wxsl:apply-templates select="document(@href)/wxsl:stylesheet/*"/>
  </wxsl:template>
  <wxsl:template match="wxsl:template|wxsl:output">
    <wxsl:if test="(contains(@mxsl-mode,$engine_name) or not(@mxsl-mode)) and (contains(@mxsl-side,$rendering_side) or not(@mxsl-side))">
      <wxsl:element name="{name()}">
        <wxsl:copy-of select="@*[name()!='mxsl-mode' and name()!='mxsl-side']"/>
        <wxsl:apply-templates/>
      </wxsl:element>
    </wxsl:if>
  </wxsl:template>
  <wxsl:template match="*">
    <wxsl:element name="{name()}">
      <wxsl:copy-of select="@*"/>
      <wxsl:apply-templates/>
    </wxsl:element>
  </wxsl:template>
</wxsl:stylesheet>
