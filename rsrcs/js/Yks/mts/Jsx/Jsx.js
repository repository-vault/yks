var Jsx = new Class({
  Implements: [Events, Options],
  Declare : 'Jsx',
  rbx:false,
  
  options: {
    url: '',
    data: '',
    rbx:false,

    headers: {},
    async: true,
    method: 'post'
  },

  initialize:function(options,anchor){
    this.setOptions(options);
    this.box= (this.anchor=$(anchor)).getBox() || Screen.getBox(options.target);
 
    var tmp = this.anchor.retrieve('jsx');
    if(tmp) this.rbx = tmp.rbx;
 },

  fire:function(chain){
    this.options.headers = {'Screen-Id':Screen.screen_id};
    if(this.options.target) this.options.headers['Content-Target'] = this.options.target;
    http_lnk(this.options, this.options.url, this.options.data, function(ret, headers){

    if(!!($type(ret)=="document" || ret.xml)) {
        this.xml_body = ret;
        var tmp = ret.getElementsByTagName("rbx")[0];
        tmp = tmp?Urls.jsx_eval(tmp.firstChild.nodeValue):(this.options.box||{});
        if(chain) tmp.chain = chain; chain = false;

        this.transform(tmp, headers['yks-render-side'] );
      } else if($type(ret)=="object") {
         this.js_valid(ret);
      }if(chain && !(ret.error||ret.stop)) chain();
    }.bind(this)); return false;
 },

 js_valid:function (rbx,anchor){ var tmp;
    if( $pick(rbx.ok, rbx.error, rbx.walk)!=null ){
        this.rbx=this.rbx || this.box.getRbx();
    } else if(this.rbx.close) this.rbx.close();

    if(rbx.walk) this.rbx.walk(rbx.walk);
    if(rbx.ok) this.rbx.msg('ok',rbx.ok); 
    if(rbx.error) this.rbx.msg('error',rbx.error);
    if(rbx.alert) alert(rbx.alert);
    if(rbx.jsx_eval) rbx.jsx_eval.bind(anchor||this.anchor)(this);
    if(rbx.set) for(key in rbx.set)window[key]=rbx.set[key];
    if(rbx.go && (tmp=(''+rbx.go).trim())) Urls.reloc(tmp=='parent'?href_ks:tmp);

 },

 xsl_prepare:function(rbx){
    http_lnk('get',xsl_path,{},function(xsl){
        window.store('transformer',new transformer_xslt(xsl));
        this.transform(rbx);
    }.bind(this));
 },

 transform:function(rbx, render_side){
    var transformer = window.retrieve("transformer");
    if(!transformer){
        if(render_side=='client')
            return this.xsl_prepare(rbx); //delayed
        transformer = new transformer_dummy();
        window.store('transformer', transformer);
    }

    var scripts = '', tmp = $n('div'), anchor;
    tmp.appendChild(transformer.out(this.xml_body)), 
    anchor = $(tmp).getElements(Doms.box_mask)[0];

    if(!anchor) return false;

    anchor.getElements("script").each(function(script){
        scripts+=script.innerHTML+";\n";
        script.dispose();
    });

    Doms.context_box= Screen.boxer(anchor,$merge({
        box_name:this.options.target,
        url:this.options.url,
        opener:this.box
    },rbx));
    Doms.context = anchor;
    if(scripts.length) $exec(scripts);
    this.js_valid(rbx, anchor);
    this.fireEvent('onPlaced');
    if(rbx.chain) rbx.chain();
 }

});



Jsx.dd_load = function(data, bind, dest){
    var box = $(bind).getBox(), url=data.url || box.url; delete data.url; data.jsx=true;

    http_lnk('post', url, data, function(txt){
        this.empty();
        Hash.each(txt, function(html,key){
          $n('option',{html:html, value:key}).inject(this);
        }.bind(this));
        this.fireEvent('change');
    }.bind($(dest)) );
}

Jsx.action = function(data, bind, confirm, chain){
    if(!bind && $type(data)=='element'){
        bind = data; data = {data:bind.toQueryString()};
    }else if($type(data)=='string') data = {ks_action:data};

    var box = $(bind).getBox(), txt= ($type(confirm)=='boolean'?$(bind).get('text'):confirm)+' ?';
    if(confirm && !window.confirm(txt)) return false;
    var url = data.url || box.url; delete data.url;
    new Jsx({url:url, target:data.target || box.box_name, data:data.data || data}, $(bind))
        .fire(chain);
}

Jsx.open = function(url, target, bind, chain){
    if(!target ) { var box = $(bind).getBox(); target = box.box_name; }
    return new Jsx({url:url, target:target, method:'get'}, $(bind)).fire(chain);
}

