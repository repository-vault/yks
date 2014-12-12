<box style="width:85%;float: right;margin-top: 5px" id="product_autocomplete">
  <?
    $product_chain = '';
    if ($product) $product_chain = "{$product->product_id} || [{$product->product_id}] {$product->product_ref} - {$product->product_name} ,";
  ?>
  <input type="text" id="product" name="product" value="<?=$product_chain?>"/>

  <domready src="/?/Yks/Scripts/Js|path://3rd/usage/TextboxList.js">
    <![CDATA[
    var productsList = new WTextboxList('product', {
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
            url: '?/Admin/Shop/Products/autocomplete//|search'
          }
        }
      }
    });
    ]]>
  </domready>
</box>