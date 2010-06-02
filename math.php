<?php

/* 
 * Computes the factoral (x!).
 * @author Thomas Oldbury. 
 * @license Public domain. 
 */ 
function bcfact($fact, $scale = 100)
{
    if($fact <= 1) return 1;
    return bcmul($fact, bcfact(bcsub($fact, '1'), $scale), $scale);
}

/* 
 * Computes e^x, where e is Euler's constant, or approximately 2.71828.
 * @author Thomas Oldbury. 
 * @license Public domain. 
 */ 
function bcexp($x, $scale = 100)
{
    /* Compute e^x. */
    $res = bcadd('1.0', $x, $scale);
    for(;;)
    {
       $new = bcadd($res, bcdiv(bcpow($x, bcadd($i, '2'), $scale), bcfact(bcadd($i, '2'), $scale), $scale), $scale);
	   if(bccomp($res, $new, $scale) == 0) break;
	   $res = $new;
    }
    return $res;
}

/* 
 * Computes ln(x).
 * @author Thomas Oldbury. 
 * @license Public domain. 
 */ 
function bcln($a, $iters = 10, $scale = 100) 
{ 
    $result = "0.0"; 
    
    for($i = 0; $i < $iters; $i++) 
    {
        $pow = bcadd("1.0", bcmul($i, "2.0", $scale), $scale);
        //$pow = 1 + ($i * 2);
        $mul = bcdiv("1.0", $pow, $scale); 
        $fraction = bcmul($mul, bcpow(bcdiv(bcsub($a, "1.0", $scale), bcadd($a, "1.0", $scale), $scale), $pow, $scale), $scale); 
        $result = bcadd($fraction, $result, $scale); 
    } 
    
    $res = bcmul("2.0", $result, $scale); 
    return $res;
} 

/* 
 * Computes a^b, where a and b can have decimal digits, be negative and/or very large.
 * Also works for 0^0. Only able to calculate up to 10 digits. Quite slow.
 * @author Thomas Oldbury. 
 * @license Public domain. 
 */ 
function bcpowx($a, $b, $iters = 25, $scale = 100)
{
    $ln = bcln($a, $iters, $scale);
    return bcexp(bcmul($ln, $b, $scale), $iters, $scale);
}

// Adapted from http://lib.stat.cmu.edu/apstat/245
// See Lanczos, C. 'A precision approximation of the gamma
//                    function', J. SIAM Numer. Anal., B, 1, 86-96, 1964.
function lngamma($n)
{
	$temp = 0.9999999999995183;
	$temp += 676.5203681218835/$n;
	$temp -= 1259.139216722289/($n+1);
	$temp += 771.3234287757674/($n+2);
	$temp -= 176.6150291498386/($n+3);
	$temp += 12.50734324009056/($n+4);
	$temp -= 0.1385710331296526/($n+5);
	$temp += 0.9934937113930748e-05/($n+6);
	$temp += 0.1659470187408462e-06/($n+7);
	return (log($temp) - 5.58106146679532777 - $n + ($n - 0.5) * log($n + 6.5));
}

function lnfact($n){
	if($n == 0 || $n == 1) return 0;
	else return lngamma($n + 1);
}
 

?>