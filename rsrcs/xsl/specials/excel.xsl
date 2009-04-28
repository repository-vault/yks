<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"  xmlns="http://www.w3.org/TR/REC-html40">


  <xsl:template match="/">

<html xmlns:x="urn:schemas-microsoft-com:office:excel">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>

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
    <xsl:apply-templates select="//table"/>
</body>
</html>
  </xsl:template>

<xsl:template match="*">
 <xsl:element name="{name()}">
    <xsl:copy-of select="@*"/>
    <xsl:apply-templates/>
  </xsl:element>
</xsl:template>

<xsl:template match="table[contains(concat(' ',@class,' '),' table ')]">
 <xsl:element name="{name()}">
    <xsl:copy-of select="@*"/>
    <xsl:apply-templates/>
  </xsl:element>
</xsl:template>

</xsl:stylesheet>