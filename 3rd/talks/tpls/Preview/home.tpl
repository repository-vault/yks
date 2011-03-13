<box theme="&pref.dft;" id="preview_box" caption="Preview" options="fly,close,resize" style="width:600px;height:400px">
<!-- ITS VERY IMPORTANT THAT THE CONTAINER BOX (of a glued element) HAS A FIXED HEIGHT -->

<button id='reloader' onclick="Jsx.action({ks_action:'preview', content:$($(this).getBox().opener).getElement('textarea').value}, this);">Reload</button>
<?if(!$_POST){?><domready>$($(this).getBox()).getElement('#reloader').click()</domready><?}?>
    <div class="glued" glued=".inner" style="width:100%;height:300px;overflow:auto">
        <?=call_user_func(array($editor, 'decode'), $content)?>
    </div>
</box>