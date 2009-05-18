

Matrix.prototype.print = function () {
    var out = "<table>";
    for (var y = 0; y < this.h; ++y) {
        out += '<tr>';
        for (var x = 0; x < this.w; ++x) {
            out += '<td>';
            out += Math.round(this.values[y][x] * 100.0) / 100.0;
            out += '</td>';
        }
        out += '</tr>';
    }
    out += '</table>';
    $('body').append(out);
    
    return this;
};

