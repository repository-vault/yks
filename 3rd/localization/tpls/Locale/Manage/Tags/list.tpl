<box theme="&pref.dft;" id="locale_tags_list" caption="Locale tags list" >
<md><button href="/?&href_fold;/manage" target="tag_add">Ajouter</button></md>

<p>Project : <select name="project_id" onchange="Jsx.open('/?&href;//'+this.value,false,this)">&select.choose;
    <?=dsp::dd($projects_dsp,array('selected'=>$project_id, 'mykse'=>'project_id', 'pad'=>"|&#160;&#160;&#160;"))?>
</select></p>

<h2>Tags libre</h2>
<table class='table'>
 <tr class='line_head'>
  <th>#</th>
  <th>Item</th>
  <th>Actions</th>
 </tr>
<?
foreach($locale_tags_list as $tag_id=>$locale_tag){

echo "<tr class='line_pair'>
    <td>{$tag_id}</td>
    <td>{$locale_tag}</td>
    <td>
    - <a href='/?&href_fold;//$tag_id/manage' target='manage_tag'>Manage</a>
    - <span onclick=\"Jsx.action({ks_action:'tag_delete',tag_id:$tag_id},this,this.innerHTML)\">Supprimer</span>
    </td>
</tr>";
}
?>
</table>
<hr/>

<h2>Choisissez un écran</h2>
<?
if($locale_tags_list)
  foreach($locale_tags_list as $tag_id=>$locale_tag){
    if(!$locale_tag->sshot_file) continue;

echo <<<EOS
<div class='locale_tag'>
    <a href='/?&href_fold;//$tag_id/tag' target="tag_edit">
        <img src='&locale.sshot_tn_path;/$tag_id.jpg'/>
    </a><br/>[{$tag_id}] {$locale_tag}</div>

EOS;

} else echo "Aucun ecran enregistré ";
?>

<clear/>



</box>