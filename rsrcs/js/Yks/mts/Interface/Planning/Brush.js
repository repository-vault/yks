var Brush = new Class({
  Declare : ['Brush'],

  initialize:function(key, use){
    this.key = key;
    this.use = (use||Planning.prototype.brush_lambda_apply).bind(this);
  }
});
