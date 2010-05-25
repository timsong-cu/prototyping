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

?>