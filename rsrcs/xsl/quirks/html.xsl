<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" version="1.0">
  <xsl:template match="a | abbr | acronym | address |  b |  bdo | big | blockquote | body | caption | canvas | cite | code | colgroup | dd | del | dfn | dl | dt | em | fieldset | form | h1 | h2 | h3 | h4 | h5 | h6 | head | i |  ins | kbd | label | legend | li |  map | noscript | object | ol | optgroup | option | p | pre | q | samp | select | small | span | strong |  sub | sup | table | tbody | td |  tfoot | th | thead | tr | tt | ul |u | var | cdata | font | *[contains(@class, 'xmlraw')]">
    <xsl:element name="{name()}">
      <xsl:copy-of select="@*"/>
      <xsl:apply-templates/>
    </xsl:element>
  </xsl:template>

<!-- force explicit closing tag -->
  <xsl:template match="iframe | script | style  | div | textarea">
    <xsl:element name="{name()}">
      <xsl:copy-of select="@*"/>
      <xsl:apply-templates/>
      <xsl:comment></xsl:comment>
    </xsl:element>
  </xsl:template>


<!-- IE create additionnal closing elements if you try to add (apply-templates) contents on an empty one -->
  <xsl:template match="base | meta | link | hr | br | param | img | area | input | col">
    <xsl:element name="{name()}">
      <xsl:copy-of select="@*"/>
    </xsl:element>
  </xsl:template>
  <xsl:template match="head/title">
    <title>
      <xsl:apply-templates/>
    </title>
  </xsl:template>
  <xsl:template match="title">
    <img src="?/Yks/Scripts/Imgs/titles//{@theme};title|{.}" class="title_{@theme} title" alt="{.}">
      <xsl:copy-of select="@*"/>
    </img>
  </xsl:template>
  <xsl:template match="empty"/>
  <xsl:template match="null">
    <xsl:apply-templates select="node()"/>
  </xsl:template>
  <xsl:template match="clear">
    <div class="clear line"> </div>
  </xsl:template>
  <xsl:template match="domready">
    <xsl:variable name="id" select="generate-id(.)"/>
    <script type="text/javascript" id="{$id}">window.addEvent('<xsl:value-of select="@event"/>' || 'domready',function(){
      <xsl:choose><xsl:when test="@src">new Asset.javascript("<xsl:value-of select="@src"/>",{ onload:function(){
            <xsl:value-of disable-output-escaping="yes" select="."/> } });
        </xsl:when><xsl:otherwise><xsl:value-of disable-output-escaping="yes" select="."/></xsl:otherwise></xsl:choose>
      }.bind(<xsl:choose><xsl:when test="$jsx">Doms.context</xsl:when><xsl:otherwise>$('<xsl:value-of select="$id"/>')</xsl:otherwise></xsl:choose>));
    </script>
  </xsl:template>
  <xsl:template match="styles/css">
    <link type="text/css" rel="stylesheet">
      <xsl:copy-of select="@href|@media"/>
    </link>
  </xsl:template>
  <xsl:template match="scripts">
    <script type="text/javascript">
      <xsl:for-each select="@*|//jsx/@*">
        <xsl:choose>
          <xsl:when test="starts-with(.,'{')">
            <xsl:value-of select="concat(name(),'=',.,';')"/>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="concat(name(),'=&quot;',.,'&quot;;')"/>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:for-each>
    </script>
    <xsl:for-each select="js">
      <script type="text/javascript"><xsl:copy-of select="@src|@defer"/>;</script>
    </xsl:for-each>
  </xsl:template>
</xsl:stylesheet>
