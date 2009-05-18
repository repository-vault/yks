
function trace(msg){
    if(!this.msg) this.msg ="";
    if(arguments.length>1)
        for(var a=1;a<arguments.length;a++)
            msg=msg.replace(/%[ds]/,arguments[a]);
    
    this.msg += msg + "<br/>";
    $('trace').innerHTML = this.msg;
}