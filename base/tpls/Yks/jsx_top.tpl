<null jsx="true">
<?php if($config->head->jsx) echo $config->head->jsx->asXML()?>
<?php if(rbx::$rbx)echo "<rbx>".jsx::encode(rbx::$rbx).'</rbx>'?>