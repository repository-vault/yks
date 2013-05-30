<box style="width:600px" theme="&pref.dft;" id="user_translation" options="fly,close,reload,modal" caption="Translation">
  User : <?=$user['user_name']?>
  <ks_form ks_action="add_language">
    <field title="Domain">
      <select name="domain" id="domain_search">
        <?=dsp::dd($locale_domains_list, array('col' => 'locale_domain_name'))?>
      </select>
    </field>
    <field title="Language">
      <input type="text" id="language_search" name="language_search"/>
    </field>
    <button>Ajouter</button>
  </ks_form>

  <box src="?<?=$href_fold?>//<?=$user['user_id']?>/list" id="user_tanslation_language" />
  <domready src="/?/Yks/Scripts/Js|path://3rd/usage/TextboxList.js">
    <![CDATA[
    var domainLst = new WTextboxList('language_search', {
      unique: false,
      max: 25,
      unique:true,
      plugins: {
        autocomplete: {
          minLength: 2,
          queryRemote: true,
          useCache: false,
          onlyFromValues:true,
          remote: {
            url: '?<?=$href_fold?>//<?=$user['user_id']?>/search//',
            extraParams: function(){
              var ret = {};
              ret.domain = $('domain_search').get('value');
              ret.time = new Date().getTime();
              return ret;
            },
            onlyFromValues:true
          }
        }
      }
    });
   ]]>
  </domready>

  </box>
