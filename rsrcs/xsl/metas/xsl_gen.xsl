<?xml version="1.0" encoding="utf-8"?>
<wxsl:stylesheet xmlns:wxsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" version="1.0"  xmlns:xsl="temp" xmlns:ewxsl="http://exslt.org/common">

<wxsl:namespace-alias stylesheet-prefix="xsl" result-prefix="wxsl"/>

<wxsl:strip-space elements="*"/>

<wxsl:param name="external_mode" value="XSL_NODE_SET"/>
<wxsl:param name="engine_name" value="gecko"/>
<wxsl:param name="myks_types_url" value="http//site_url"/>

<wxsl:template match="/wxsl:stylesheet">
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
		<wxsl:attribute name="select">document('<wxsl:value-of select="$myks_types_url"/>')</wxsl:attribute>
	      </wxsl:element>
	   </wxsl:when>
	   <wxsl:when test="$external_mode='XSL_NODE_SET'">
	      <wxsl:element name="xsl:variable">
		<wxsl:attribute name="name">myks_types_tree</wxsl:attribute>
		<wxsl:copy-of select="document($myks_types_url)/*"/>
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



  <wxsl:template match="wxsl:template">

    <wxsl:if test="contains(@mode,$engine_name)">
      <wxsl:element name="{name()}">
        <wxsl:copy-of select="@name|@match"/>
        <wxsl:apply-templates/>
      </wxsl:element>
    </wxsl:if>

    <wxsl:if test="not(@mode)">
      <wxsl:element name="{name()}">
        <wxsl:copy-of select="@*"/>
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
