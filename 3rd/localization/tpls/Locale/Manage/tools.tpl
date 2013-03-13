<box theme="&pref.dft;" caption="Locale tool-box">
<div><span style='color:orange;font-weight:bold'>WARN</span> : Dont abuse of cache deletion ! </div>
  <ks_form ks_action="clean_locale_cache" submit="Clean locale cache">
    <fields>
      <field title="Locale Domain">
        <select id='locale_domain_id' name='locale_domain_id[]' multiple="multiple">
          <?=dsp::dd($locale_domains_list,array('col'=>'locale_domain_name'))?>
        </select>
      </field>
    </fields>
  </ks_form>
</box>