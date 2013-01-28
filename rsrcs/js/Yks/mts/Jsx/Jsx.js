
var Jsx = new Class({
  Implements: [Events, Options],
  Declare : 'Jsx',

  rbx:false,
  chain:false,
  data:[],

  options: {
    url: '',
    rbx:false,
    encoding:"urlencode", //multipart
    headers: {},
    async: true,
    method: 'POST'
  },

  initialize:function(options, anchor){
    this.setOptions(options);
    this.box= (this.anchor=$(anchor)).getBox() || Screen.getBox(options.target);
 
    var tmp = this.anchor.retrieve('jsx');
    if(tmp) this.rbx = tmp.rbx;
  },

  data_reset:function(){
    this.data = [];
  },

  hash_stack:function(hash){
    var mytype = $type(hash);
    if( mytype == 'string' || mytype =='element' || mytype == 'array' || mytype == 'hash' )
        return this.data_stack(hash);

    $H(hash).each(function(value, key){
        this.data_stack({key:key,value:value});
    }.bind(this));
    return this;
  },

  data_stack:function(data){
    if($type(data) == 'array') {
        this.data.extend(data);
    } else this.data.push(data);
    return this;
  },

  fire:function(chain){
    var xhr = new Xhr(this.options.async, this.options.encoding);
    xhr.addHeaders({'Yks-Jsx':1, 'Yks-Client-TZ':-(new Date()).getTimezoneOffset()*60 });
    xhr.addEvent('success', this.process_http_response.bind(this));

    if(this.options.method == 'POST')
        this.data_stack({key:'ks_flag', value:Jsx.security_flag});

    this.chain = chain;
    xhr.request(this.options.url, this.options.method, this.data);

    return false;
  },

//this cannot be bound by using Binds mutator
  process_http_response:function(ret, headers){
    if(!!($type(ret)=="document" || ret.xml)) {
        this.xml_body = ret;
        var tmp = ret.getElementsByTagName("rbx")[0];
      if(tmp)
          tmp.parentNode.removeChild(tmp);
        tmp = tmp?Urls.jsx_eval(tmp.firstChild.nodeValue):(this.options.box||{});
        this.transform(tmp, headers['yks-render-side'] );
    } else if($type(ret)=="object") {
        this.js_valid(ret);
    } if(this.chain && !(ret.error||ret.stop))
        this.consume_chain(ret);

  },

  js_valid:function (rbx, anchor){
    if( $pick(rbx.ok, rbx.error, rbx.walk)!=null ){
        this.rbx = this.rbx || this.box.getRbx();
    } else if(this.rbx.close) this.rbx.close();

    if(rbx.walk) this.rbx.walk(rbx.walk);
    if(rbx.ok) this.rbx.msg('ok',rbx.ok); 
    if(rbx.error) this.rbx.msg('error',rbx.error);
    if(rbx.alert) alert(rbx.alert);
    if(rbx.jsx_eval) rbx.jsx_eval.bind(anchor||this.anchor)(this);
    if(rbx.set) for(key in rbx.set) window[key] = rbx.set[key];
    if(rbx.go) {
         var tmp = (''+rbx.go).trim();
         Urls.reloc(tmp == 'parent' ? href_ks : tmp);
    }
  },

  xsl_prepare:function(rbx){
    Xhr.http_lnk('get',xsl_path,{},function(xsl){
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

    Screen.boxer(anchor, $merge({
        box_name:this.options.target,
        url:this.options.url,
        opener:this.box
    },rbx));

    Doms.context = anchor;
    if(scripts.length) $exec(scripts);
    this.js_valid(rbx, anchor);
    this.fireEvent('onPlaced');
    this.consume_chain(rbx);
  },

  consume_chain:function(rbx){
    if(!this.chain) return;
    this.chain(rbx);
    this.chain = false;
  }

});

