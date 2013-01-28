<box theme="&pref.dft;" caption="Details deletion &zks_deletion_id;" options="modal,fly,close">
<textarea style="width:700px;height:600px"><?
print_r(json_decode($deletion['deletion_blob'],1));
?></textarea>
</box>