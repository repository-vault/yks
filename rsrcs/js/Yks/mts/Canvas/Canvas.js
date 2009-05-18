
var CanvasRenderingContext2D_prototype = ($defined(window.CanvasRenderingContext2D)
      ?CanvasRenderingContext2D
      :new Element('canvas').getContext('2d').constructor
     ).prototype;


$extend(CanvasRenderingContext2D_prototype, {
  square:function(points){
    this.beginPath();
    this.moveTo(points[0][0],points[0][1]);
    this.lineTo(points[1][0],points[1][1]);
    this.lineTo(points[2][0],points[2][1]);
    this.lineTo(points[3][0],points[3][1]);
    this.closePath();
    return this;
  },
  
  point_projection_2d:function(angle, y, ray, Kx, Ky) {
    return [Kx*Math.tan(angle), Ky*(y/(ray * Math.cos(angle)))];
  },
  
  box_projection_2d:function(angle_start, angle, top, height, ray, Kx, Ky) {
    return [
      this.point_projection_2d(angle_start, top, ray, Kx, Ky),
      this.point_projection_2d(angle_start+angle, top, ray, Kx, Ky),
      this.point_projection_2d(angle_start+angle, top-height, ray, Kx, Ky),
      this.point_projection_2d(angle_start, top-height, ray, Kx, Ky)
    ];
  } 
});