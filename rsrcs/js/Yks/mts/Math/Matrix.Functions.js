/**
 * Calculate a projective transform that maps [0,1]x[0,1] onto the given set of points.
 */
 
Matrix.getProjectiveTransform= function(points) {
  var eqMatrix = new Matrix(9, 8, [
    [ 1, 1, 1,   0, 0, 0, -points[2][0],-points[2][0],-points[2][0] ], 
    [ 0, 1, 1,   0, 0, 0,  0,-points[3][0],-points[3][0] ],
    [ 1, 0, 1,   0, 0, 0, -points[1][0], 0,-points[1][0] ],
    [ 0, 0, 1,   0, 0, 0,  0, 0,-points[0][0] ],

    [ 0, 0, 0,  -1,-1,-1,  points[2][1], points[2][1], points[2][1] ],
    [ 0, 0, 0,   0,-1,-1,  0, points[3][1], points[3][1] ],
    [ 0, 0, 0,  -1, 0,-1,  points[1][1], 0, points[1][1] ],
    [ 0, 0, 0,   0, 0,-1,  0, 0, points[0][1] ]

  ]);
  
  var kernel = eqMatrix.rowEchelon().values;
  var transform = new Matrix(3, 3, [
    [-kernel[0][8], -kernel[1][8], -kernel[2][8]],
    [-kernel[3][8], -kernel[4][8], -kernel[5][8]],
    [-kernel[6][8], -kernel[7][8],             1]
  ]);
  return transform;
}
