<?


define('GRAY_LVL',127);
define('COLOR_GRAY', coloralpha(GRAY_LVL, GRAY_LVL, GRAY_LVL));


function coloralpha($r=255,$g=255,$b=255,$alpha=0){ return ($alpha<<24)+($r<<16)+($g<<8)+$b; }
function colorget($c){
	return coloralpha((int)$c['red'],(int)$c['green'],(int)$c['blue'],(int)$c['alpha']);
}
function colordec($c){
	return array(
		'alpha'=>($c>>24)&0x7F,
		'red'=>($c>>16)&0xFF,
		'green'=>($c>>8)&0xFF,
		'blue'=>$c&0xFF
	);
}

function gray($c){
    return alphablend(colordec($c));
    return  floor(((($c>>16)&0xFF) + (($c>>8)&0xFF) + ($c&0xFF))/3);
}

function alphablend($color, $bg=255){
    return min(floor((($color['alpha'])/127)*$bg+((127-$color['alpha'])/127)*$color['red']),255);
    
}

/*
    Calcule la couleur resultante de la superposition de deux autres, en supportant leur canal alpha
    Ca rouske.
*/
function colorfusion($dest,$mask){
    //en.wikipedia.org/wiki/Alpha_transparency
    $aa=(127-$mask['alpha'])/127;$ab=(127-$dest['alpha'])/127;
    $na=($aa+$ab*(1-$aa));

    return array(
        'alpha'=> 127-$na*127,
        'red'=> (int)($na?($aa*$mask['red']+(1-$aa)*$ab*$dest['red'])/$na:0),
        'green'=> (int)($na?($aa*$mask['green']+(1-$aa)*$ab*$dest['green'])/$na:0),
        'blue'=> (int)($na?($aa*$mask['blue']+(1-$aa)*$ab*$dest['blue'])/$na:0),
    );
}

 /* return the color 'value' (from 0 to 1), based on gray level & considering alpha
    use this for setting alpha level :   (1-colorvalue($color))*127;
    or for setting a gray level :        colorvalue($color) * 255
 */
function colorvalue($color){
    $color = colordec($color);
    $color_level = (255-colorgray($color))/255;
    return ((127-$color['alpha'])/127)*$color_level;
}


/*
    There is no one "correct" conversion from RGB to grayscale, since it depends on the sensitivity response curve of your detector to light as a function of wavelength. A common one in use is:
    Y = 0.3*R + 0.59*G + 0.11*B
*/

function colorgray($color){
    return (int)$color['red']*0.3+$color['green']*0.59+$color['blue']*0.11;
}
