<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml">

  <xsl:template match="video"  mode="gecko,webkit,presto">
    <object type="application/x-ms-wmp" data="{@src}">
      <xsl:copy-of select="@id|@style"/>
      <param name="url" value="{@src}" />
      <param name="src" value="{@src}" />
      <param name="showcontrols" value="false"/>
      <param name="autoStart" value="true"/>
      <param name="uiMode" value="none" />
    </object>
  </xsl:template>

  <xsl:template match="video" mode="trident">
    <object type="video/x-ms-wmv" data="{@src}">
      <xsl:copy-of select="@id|@style"/>
      <param name="url" value="{@src}" />
      <param name="src" value="{@src}" />
      <param name="showcontrols" value="false"/>
      <param name="autoStart" value="true"/>
      <param name="uiMode" value="none" />
    </object>
  </xsl:template>

</xsl:stylesheet>