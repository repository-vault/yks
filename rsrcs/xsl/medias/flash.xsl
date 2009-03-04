<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml">
  <xsl:template match='flash'>
    <object type="application/x-shockwave-flash" data="{@src}">
        <xsl:copy-of select="@id|@style"/>
        <param name="movie" value="{@src}"/>
        <param name="quality" value="high" />
        <p><a href='http://www.macromedia.com/go/getflashplayer'>Get macromedia</a></p>
    </object>
  </xsl:template>
</xsl:stylesheet>