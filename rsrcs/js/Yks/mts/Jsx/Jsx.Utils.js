
Jsx.action = function(data, bind, confirm, chain){
    if(!bind && $type(data)=='element'){
        bind = data; data = {data:bind.toQueryString()};
    }else if($type(data)=='string') data = {ks_action:data};

    var box = $(bind).getBox(), txt= ($type(confirm)=='boolean'?$(bind).get('text'):confirm)+' ?';
    if(confirm && !window.confirm(txt)) return false;
    var url = data.url || box.url; delete data.url;
    new Jsx({url:url, target:data.target || box.box_name}, $(bind))
        .hash_stack(data.data || data).fire(chain);
};

Jsx.open = function(url, target, bind, chain){
    if(!target ) { var box = $(bind).getBox(); target = box.box_name; }
    return new Jsx({url:url, target:target, method:'get'}, $(bind)).fire(chain);
};


Jsx.dd_load = function(data, bind, dest){
    var box = $(bind).getBox(), url=data.url || box.url; delete data.url; data.jsx=true;

    Xhr.http_lnk('post', url, data, function(txt){
        this.empty();
        Hash.each(txt, function(html,key){
          $n('option',{html:html, value:key}).inject(this);
        }.bind(this));
        this.fireEvent('change');
    }.bind($(dest)) );
};


