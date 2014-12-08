
Jsx.action = function(data, bind, confirm, chain){
    if(!bind && $type(data)=='element'){
        bind = data; data = {data:bind.toQueryString()};
    } else if($type(data)=='string') data = {ks_action:data};

    var box = document.id(bind).getBox(), txt= ($type(confirm)=='boolean'? document.id(bind).get('text'):confirm)+' ?';
    if(confirm && !window.confirm(txt)) return false;
    var url = data.url || box.url; delete data.url;

    var jsx = new Jsx({url:url, target:data.target || box.box_name}, document.id(bind));
    jsx.hash_stack(data.data || data);
    (jsx.rbx = jsx.rbx || box.getRbx() ).loader();
    jsx.fire(chain);
};

Jsx.open = function(url, target, bind, chain){
    if(!target ) { var box = document.id(bind).getBox(); target = box.box_name; }
    return new Jsx({url:url, target:target, method:'get'}, document.id(bind)).fire(chain);
};

Jsx.popup = function(url, options, target){
    window.open(url, target || "_blank", 'menubar=no,location=no,scrollbars=no,status=no,titlebar=no,' + options);
}


Jsx.dd_load = function(data, bind, dest){
    var box = document.id(bind).getBox(), url=data.url || box.url; delete data.url; data.jsx=true;

    Xhr.http_lnk('post', url, data, function(txt){
        this.empty();
        Hash.each(txt, function(html,key){
          $n('option',{text: html, value:key}).inject(this);
        }.bind(this));
        this.fireEvent('change');
    }.bind( document.id(dest)) );
};

Jsx.sendForm = function(ks_action, formId) {
  var datablob = $(formId).toQueryString().parseQueryString();
  datablob.ks_action = ks_action;
  return datablob;
}


