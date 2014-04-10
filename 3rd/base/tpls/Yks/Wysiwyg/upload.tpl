<?php echo <<<EOS

<box id="upload_file" theme="&pref.dft;" caption="&wysiwyg.file_attach;" options="close,modal,fly">
<ks_form ks_action="upload_tmp" enctype="multipart/form-data" submit="&action.upload_tmp;" action="/?$href_fold//$upload_flag;$upload_src;$upload_type/upload" target="upload_frame">

<span style="font-size:10px">(&wysiwyg.valid_exts; : {$upload_def['exts']} ; poids maxi. : {$max_size})</span>

  <fields>
	<input type="hidden" name="APC_UPLOAD_PROGRESS" value="$upload_flag" />
	<field title="&wysiwyg.file_name;"  name="user_file" type="file"/>
  </fields>

</ks_form>

<div style="display:none"><iframe name="upload_frame" id="upload_frame" src="/?/Yks/Wysiwyg/blank"> </iframe> </div>
</box>

EOS;

