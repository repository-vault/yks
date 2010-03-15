<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE stylesheet [
<!ENTITY match_full "col">
<!ENTITY match_element "b|big | div | em | h2 | h3 | h4 | h5 | h6 | i | p | span | strong | tbody | td | tfoot | th | thead | tr | u | cdata | font">
<!ENTITY match_onlybody "a|*">
]>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/TR/REC-html40" xmlns:xls="excel" version="1.0">
  <xsl:output method="xml" version="1.0" encoding="utf-8" omit-xml-declaration="yes"/>
  <xsl:template match="/">
    <html xmlns:x="urn:schemas-microsoft-com:office:excel">
      <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <style>
    tr {mso-height-source:auto;}
    th, col {mso-width-source:userset;}
    br {mso-data-placement:same-cell;}
    td {
      mso-number-format:"\@";
      white-space:normal;
    }
    <xsl:value-of select="//xls:style"/>
</style>
        <xsl:comment><![CDATA[[if gte mso 9]>
<xml>
 <x:ExcelWorkbook>
  <x:ExcelWorksheets>
   <x:ExcelWorksheet>
    <x:Name>Data</x:Name>
    <x:WorksheetOptions>
     <x:Selected/>
    </x:WorksheetOptions>
   </x:ExcelWorksheet>
  </x:ExcelWorksheets>
 </x:ExcelWorkbook>
</xml><![endif]]]></xsl:comment>
      </head>
      <body>
        <xsl:apply-templates select="//table[contains(concat(' ',@class,' '),' table ')]"/>
      </body>
    </html>
  </xsl:template>
  <xsl:template match="table[contains(concat(' ',@class,' '),' table ')]">
    <xsl:element name="{name()}">
      <xsl:copy-of select="@*"/>
      <xsl:apply-templates/>
    </xsl:element>
  </xsl:template>
  <xsl:template match="br">
    <br/>
  </xsl:template>
  <xsl:template match="input">
    <xsl:choose>
      <xsl:when test="@checked and not(@disabled)">Yes</xsl:when>
      <xsl:when test="@checked and @disabled">(Yes)</xsl:when>
      <xsl:otherwise> </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
<!-- Keep element & args & body -->
  <xsl:template name="full" match="&match_full;">
    <xsl:element name="{name()}">
      <xsl:copy-of select="@*"/>
      <xsl:apply-templates/>
    </xsl:element>
  </xsl:template>
<!-- Keep element & body -->
  <xsl:template name="element" match="&match_element;">
    <xsl:element name="{name()}">
      <xsl:apply-templates/>
    </xsl:element>
  </xsl:template>
<!-- Keep body only -->
  <xsl:template name="onlybody" match="&match_onlybody;">
    <xsl:apply-templates/>
  </xsl:template>
</xsl:stylesheet>
