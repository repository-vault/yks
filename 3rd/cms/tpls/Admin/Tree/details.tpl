<box id="node_infos">

<style>
#node_infos textarea {
  width:100%;
  height:300px;
}

</style>
<p>Node : <a href='/?&href;'><?=$node?></a> <a target='box_preview' href='/?&href_base;/preview'>Preview</a></p>

<?=($node->parent_node?"Parent : <a href='/?&href_fold;//{$node->parent_node->node_id}/details'>{$node->parent_node}</a>":"- no parent-")?>


<p>Versions : <select onchange="Jsx.open('/?&href;//'+this.value, false, this)">
    &select.choose;
    <?=dsp::dd($versions, $revision_date)?>
</select>
</p>


<ks_form ks_action="node_save" submit="save">
    <field title="Key" type="title" name="node_key" value="<?=$node->node_key?>"/>

    <field title="Parent" type="title" name="parent_id" value="<?=$node->parent_id?>"/>
<?php

foreach($node->editables as $node_property){

  echo "
    $node_property
    <textarea name='$node_property'>".specialchars_encode($node->{$node_property})."&XML_EMPTY;</textarea>
  ";

}


?>


</ks_form>


</box>