<box theme="&pref.dft;" id="preview_box" caption="Preview" options="fly,close" style="width:640px;height:600px">

<button id='reloader' onclick="Jsx.action({ks_action:'preview', content:$($(this).getBox().opener).getElement('textarea').value}, this);">Reload</button>
<?if(!$_POST){?><domready>$($(this).getBox()).getElement('#reloader').click()</domready><?}?>
    <div>
        <?=call_user_func(array($editor, 'decode'), $content)?>
    </div>
</box>