/**
 * Projective texturing using Canvas.
 * by 131, based on Steven Wittens projective.js
 * http://acko.net/files/projective/projective.js
 */

var Canvas = new Class({
  Declare:['Canvas'],

  canvas:null,
  ctx:null,
  
  points:[],

  options:{
    wireframe: true,
    subdivisionLimit: 5,
    patchSize: 64
  },

  image:null,

    //matrice de transformation depuis les points
  transform:null,
  image_metas:{
    width:null,
    height:null
  },
  
  initialize:function(el, options){
    this.canvas = el;
    if(options) this.options = options;
  },
  
  setImage:function(image){
    this.image = image;
    this.image_metas = {
        width:this.image.width,
        height:this.image.height
    };
  },
  dispose:function(){
    this.canvas.dispose();
  },

  update:function(){
      //calc canvas dims
    var minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
    $A(this.points).each(function(v) {
      minX = Math.min(minX, Math.floor(v[0]));
      maxX = Math.max(maxX, Math.ceil(v[0]));
      minY = Math.min(minY, Math.floor(v[1]));
      maxY = Math.max(maxY, Math.ceil(v[1]));
    }); minX--; minY--; maxX++; maxY++;
    var width = maxX - minX, height = maxY - minY;


    // Reshape canvas.
    this.canvas.style.marginLeft = minX +'px';
    this.canvas.style.marginTop = minY +'px';
    this.canvas.width = width;
    this.canvas.height = height;


    // Set up basic drawing context.
    this.ctx = this.canvas.getContext("2d");
    this.ctx.translate(-minX, -minY);
    this.ctx.clearRect(minX, minY, width, height);
    this.ctx.strokeStyle = "rgb(220,0,100)";

    this.transform = Matrix.getProjectiveTransform(this.points);

    // Begin subdivision process.
    var ptl = this.transform.transformProjectiveVector([0, 0, 1]);
    var ptr = this.transform.transformProjectiveVector([1, 0, 1]);
    var pbl = this.transform.transformProjectiveVector([0, 1, 1]);
    var pbr = this.transform.transformProjectiveVector([1, 1, 1]);

    this.ctx.square([ptl, ptr, pbr,pbl]).clip();

    this.divide(0, 0, 1, 1, ptl, ptr, pbl, pbr, this.options.subdivisionLimit);

    if (this.options.wireframe) 
        this.ctx.square([ptl, ptr, pbr,pbl]).stroke();
  },


  divide: function(u1, v1, u4, v4, p1, p2, p3, p4, limit) {

    // See if we can still divide.
    if (limit) {
       // Measure patch non-affinity.
      var d1 = [p2[0] + p3[0] - 2 * p1[0], p2[1] + p3[1] - 2 * p1[1]];
      var d2 = [p2[0] + p3[0] - 2 * p4[0], p2[1] + p3[1] - 2 * p4[1]];
      var d3 = [d1[0] + d2[0], d1[1] + d2[1]];
      var r = Math.abs((d3[0] * d3[0] + d3[1] * d3[1]) / (d1[0] * d2[0] + d1[1] * d2[1]));

      // Measure patch area.
      d1 = [p2[0] - p1[0] + p4[0] - p3[0], p2[1] - p1[1] + p4[1] - p3[1]];
      d2 = [p3[0] - p1[0] + p4[0] - p2[0], p3[1] - p1[1] + p4[1] - p2[1]];
      var area = Math.abs(d1[0] * d2[1] - d1[1] * d2[0]);

      // Check area > patchSize pixels (note factor 4 due to not averaging d1 and d2)
      // The non-affinity measure is used as a correction factor.
      var deeper = (u1 == 0 && u4 == 1)
          || ((.25 + r * 5) * area > (this.options.patchSize * this.options.patchSize));
      if (deeper) {
        this.subdivide(u1, v1, u4, v4, p1, p2, p3, p4, limit);
        return;
      }
    }

    // Render this patch.
    this.ctx.save();
    this.ctx.square([p1, p2, p3, p4]); //.clip();

    // Get patch edge vectors.
    var d12 = [p2[0] - p1[0], p2[1] - p1[1]];
    var d24 = [p4[0] - p2[0], p4[1] - p2[1]];
    var d43 = [p3[0] - p4[0], p3[1] - p4[1]];
    var d31 = [p1[0] - p3[0], p1[1] - p3[1]];

    // Find the corner that encloses the most area
    var a1 = Math.abs(d12[0] * d31[1] - d12[1] * d31[0]);
    var a2 = Math.abs(d24[0] * d12[1] - d24[1] * d12[0]);
    var a4 = Math.abs(d43[0] * d24[1] - d43[1] * d24[0]);
    var a3 = Math.abs(d31[0] * d43[1] - d31[1] * d43[0]);
    var amax = Math.max(Math.max(a1, a2), Math.max(a3, a4));
    var dx = 0, dy = 0, padx = 0, pady = 0;

      // Align the transform along this corner.
    switch (amax) {
      case a1:
        this.ctx.transform(d12[0], d12[1], -d31[0], -d31[1], p1[0], p1[1]);
        // Calculate 1.05 pixel padding on vector basis.
        if (u4 != 1) padx = 1.05 / Math.sqrt(d12[0] * d12[0] + d12[1] * d12[1]);
        if (v4 != 1) pady = 1.05 / Math.sqrt(d31[0] * d31[0] + d31[1] * d31[1]);
        break;
      case a2:
        this.ctx.transform(d12[0], d12[1],  d24[0],  d24[1], p2[0], p2[1]);
        // Calculate 1.05 pixel padding on vector basis.
        if (u4 != 1) padx = 1.05 / Math.sqrt(d12[0] * d12[0] + d12[1] * d12[1]);
        if (v4 != 1) pady = 1.05 / Math.sqrt(d24[0] * d24[0] + d24[1] * d24[1]);
        dx = -1;
        break;
      case a4:
        this.ctx.transform(-d43[0], -d43[1], d24[0], d24[1], p4[0], p4[1]);
        // Calculate 1.05 pixel padding on vector basis.
        if (u4 != 1) padx = 1.05 / Math.sqrt(d43[0] * d43[0] + d43[1] * d43[1]);
        if (v4 != 1) pady = 1.05 / Math.sqrt(d24[0] * d24[0] + d24[1] * d24[1]);
        dx = -1;
        dy = -1;
        break;
      case a3:
        // Calculate 1.05 pixel padding on vector basis.
        this.ctx.transform(-d43[0], -d43[1], -d31[0], -d31[1], p3[0], p3[1]);
        if (u4 != 1) padx = 1.05 / Math.sqrt(d43[0] * d43[0] + d43[1] * d43[1]);
        if (v4 != 1) pady = 1.05 / Math.sqrt(d31[0] * d31[0] + d31[1] * d31[1]);
        dy = -1;
        break;
    }

    // Calculate image padding to match.
    var du = (u4 - u1), dv = (v4 - v1);
    var padu = padx * du, padv = pady * dv;
    this.ctx.drawImage(
      this.image,
      u1 * this.image_metas.width,
      v1 * this.image_metas.height,
      Math.min(u4 - u1 + padu, 1) * this.image_metas.width,
      Math.min(v4 - v1 + padv, 1) * this.image_metas.height,
      dx, dy,
      1 + padx, 1 + pady
    );
    this.ctx.restore();
  },

    // Calculate subdivision points (middle, top, bottom, left, right).
  subdivide:function(u1, v1, u4, v4, p1, p2, p3, p4, limit){
    var umid = (u1 + u4) / 2, vmid = (v1 + v4) / 2;
    var pmid = this.transform.transformProjectiveVector([umid, vmid, 1]);
    
    var pt = this.transform.transformProjectiveVector([umid, v1, 1]);
    var pb = this.transform.transformProjectiveVector([umid, v4, 1]);
    var pl = this.transform.transformProjectiveVector([u1, vmid, 1]);
    var pr = this.transform.transformProjectiveVector([u4, vmid, 1]);

    // Recurse
    limit--;
    this.divide(u1, v1, umid, vmid, p1, pt, pl, pmid, limit);
    this.divide(umid, v1, u4, vmid, pt, p2, pmid, pr, limit);
    this.divide(u1, vmid, umid, v4, pl, pmid, p3, pb, limit);
    this.divide(umid, vmid, u4, v4, pmid, pr, pb, p4, limit);

    if(this.options.wireframe)
      this.clipDebug(this.ctx, pt, pb, pl, pr);
  },

  
  clipDebug:function (ctx, pt, pb, pl, pr){
    ctx.beginPath();
    ctx.moveTo(pt[0], pt[1]);
    ctx.lineTo(pb[0], pb[1]);
    ctx.stroke();

    ctx.beginPath();
    ctx.moveTo(pl[0], pl[1]);
    ctx.lineTo(pr[0], pr[1]);
    ctx.stroke();
  },
  
  perspective:function(points){
    this.points = points;
    if(this.image) this.update();
  }

});

$.Canvas = function(canvas){ return canvas.canvas; }
