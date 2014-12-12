<box style="width:85%;float: right;margin-top: 5px" options="modal,fly,close,reload" id="category_autocomplete">
  <?
    $category_chain = '';
    if ($category) $category_chain = $category->category_id .'||'. $category->category_name .',';
  ?>
  <input type="text" id="category" name="category" value="<?=$category_chain?>"/>

  <domready src="/?/Yks/Scripts/Js|path://3rd/usage/TextboxList.js">
    <![CDATA[
    var categoriesList = new WTextboxList('category', {
      max: 5,
      unique:true,
      decode: function(o){
        var tmp = [o];
        return tmp.map(function(v) {
          return v.split('||');
        });
      },
      plugins: {
        autocomplete: {
          minLength: 1,
          maxResults: 100,
          queryRemote: true,
          useCache: false,
          overflowResults: true,
          method: 'delegate',
          remote: {
            url: '?/Admin/Shop/Categories/autocomplete//|search'
          }
        }
      }
    });
    ]]>
  </domready>
</box>