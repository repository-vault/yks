<?php echo <<<EOS

<box id="upload_file" theme="&pref.dft;" caption="&wysiwyg.file_attach;" options="close,modal,fly">
<ks_form ks_action="upload_tmp" enctype="multipart/form-data" submit="&action.upload_tmp;" method="PUT" action="/?$href_fold//$upload_flag;$upload_src;$upload_type/upload">

<span style="font-size:10px">(&wysiwyg.valid_exts; : {$upload_def['exts']} ; poids maxi. : {$upload_def['size']} Ko)</span>

  <fields>

	<field title="&wysiwyg.file_name;"  name="user_file" type="file"/>
  </fields>

</ks_form>

</box>

EOS;

