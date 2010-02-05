<?=<<<EOS
<box id="mail_part_$part_id" class="mail_part_box">
    - <a href='/?$href_fold//$part_id;1'>Part $part_id, (Type : {$part_infos['content-type']})</a>
    <div class='part_actions'>
        
        <a class='trash' onclick="Jsx.action('part_delete',this,'Supprimer')"> </a>
    </div>


EOS;
