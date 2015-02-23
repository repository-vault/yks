<?xml version="1.0" encoding="utf-8"?>
<wxsl:stylesheet xmlns:wxsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="temp" xmlns:ewxsl="http://exslt.org/common" version="1.0">
  <wxsl:namespace-alias stylesheet-prefix="xsl" result-prefix="wxsl"/>
  <wxsl:strip-space elements="*"/>
  <!-- params -->
  <wxsl:param name="rendering_side" value="client"/>
  <wxsl:param name="engine_name" value="gecko"/>
  <!-- /params -->
  <wxsl:template match="/wxsl:stylesheet">
    <xsl:stylesheet>
      <wxsl:copy-of select="@*"/>
      <wxsl:apply-templates select="wxsl:output|wxsl:variable"/>
      <wxsl:apply-templates select="wxsl:import"/>
      <wxsl:apply-templates select="wxsl:template"/>
    </xsl:stylesheet>
  </wxsl:template>
  <wxsl:template match="wxsl:import">
    <wxsl:apply-templates select="document(@href)/wxsl:stylesheet/*"/>
  </wxsl:template>
  <wxsl:template match="*">
    <wxsl:if test="(contains(@mxsl-mode,$engine_name) or not(@mxsl-mode)) and (contains(@mxsl-side,$rendering_side) or not(@mxsl-side))">
      <wxsl:element name="{name()}">
        <wxsl:copy-of select="@*[name()!='mxsl-mode' and name()!='mxsl-side']"/>
        <wxsl:apply-templates/>
      </wxsl:element>
    </wxsl:if>
  </wxsl:template>
</wxsl:stylesheet>
