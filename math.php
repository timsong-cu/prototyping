<?php

define('FPMIN', '1.0e-30');
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
	if($n == 0)
		var_dump(debug_backtrace());
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

function binomial_pmf($n, $p, $k){
	$lnpmf = lngamma($n+1) - lngamma($k+1) - lngamma($n-$k+1)
			 + $k * log($p) + ($n-$k) * log(1-$p);
	return exp($lnpmf);
}

function binomial_cdf($n, $p, $k){
	if($k >= $n) return 1;
	return betai($n - $k, $k + 1, 1 - $p);
}

// Adapted from Numerical Recipes in C, 2e
function betai($a, $b, $x){
	if ($x < 0.0 || $x > 1.0) die("Invalid parameter to betai");
	if ($x == 0.0 || $x == 1.0) $bt = 0.0;
	else //Factors in front of the continued fraction.
	$bt=exp(lngamma($a+$b)-lngamma($a)-lngamma($b)+$a*log($x)+$b*log(1.0-$x));
	
	if ($x < ($a+1.0)/($a+$b+2.0)) // Use continued fraction directly.
		return $bt*betacf($a,$b,$x)/$a;
	else //Use continued fraction after making the symmetry transformation
		return 1.0-$bt*betacf($b,$a,1.0-$x)/$b;
}

//
//Used by betai: Evaluates continued fraction for incomplete beta function.
function betacf($a, $b, $x){
	$qab=$a+$b;
	$qap=$a+1.0;
	$qam=$a-1.0;
	$c=1.0; //First step of Lentz’s method.
	$d=1.0-$qab*$x/$qap;
	if (abs($d) < FPMIN) $d = FPMIN;
	$d=1.0/$d;
	$h=$d;
	for ($m=1;$m<=100;$m++) {
		$m2=2*$m;
		$aa=$m*($b-$m)*$x/(($qam+$m2)*($a+$m2));
		$d=1.0+$aa*$d;// One step (the even one) of the recurrence.
		if (abs($d) < FPMIN) $d=FPMIN;
		$c=1.0+$aa/$c;
		if (abs($c) < FPMIN) $c=FPMIN;
		$d=1.0/$d;
		$h *= $d*$c;
		$aa = -($a+$m)*($qab+$m)*$x/(($a+$m2)*($qap+$m2));
		$d=1.0+$aa*$d; // Next step of the recurrence (the odd one).
		if (abs($d) < FPMIN) $d=FPMIN;
		$c=1.0+$aa/$c;
		if (abs($c) < FPMIN) $c=FPMIN;
		$d=1.0/$d;
		$del=$d*$c;
		$h *= $del;
		if (abs($del-1.0) < 3.0e-7) break;
	}
	if ($m > 100) die("a or b too big, or MAXIT too small in betacf");
	return $h;
}
?>