<?php
require_once('../bcmathext.php');
/**
 * This file contains the actual algorithm for computing p-values by Fisher's exact test
 * on a 2x2 contingency table.
 */


/* Compute p-value using Fisher's exact test on a 2x2 contingency table.
 * 		X1		X2      
 * Y1	$n11	$n21
 * Y2	$n12	$n22
 * 	
 * @param int $n11
 * @param int $n21
 * @param int $n12
 * @param int $n22 
 * @param int $scale the number of digits after the decimal point to return, default 15.
 * @return string the p-value computed by fisher's exact test.
 */
function fishertest($n11, $n21, $n12, $n22, $scale=15){
	$ret = "0.0";
	$det = $n11 * ($n12 + $n22) - $n12 * ($n11 + $n21);
	
	if($det > 0){ // upper ratio larger than lower, decrement n21/n12 for more extreme cases
		$minvalue = $n12 > $n21 ? $n21 : $n12;
		for(; $minvalue >= 0; $n11++, $n21--, $n12--, $n22++, $minvalue--){
			$prob = fisher_probability($n11, $n21, $n12, $n22, $scale);
			$ret = bcadd($prob, $ret, $scale);
		}
	}
	else{
		$minvalue = $n11 > $n22 ? $n22 : $n11;
		for(; $minvalue >= 0; $n11--, $n21++, $n12++, $n22--, $minvalue--){
			$prob = fisher_probability($n11, $n21, $n12, $n22, $scale);
			$ret = bcadd($prob, $ret, $scale);
		}
	}
	
	return $ret;
}
function fisher_probability($n11, $n21, $n12, $n22, $scale=15){
	$denom = "1"; // the denominator
	$numer = "1"; // the numerator
	if($n11 < 0) $n11 = 0;
	if($n21 < 0) $n11 = 0;
	if($n12 < 0) $n11 = 0;
	if($n22 < 0) $n11 = 0;
	if($scale < 0) $n11 = 0;
	
	for($i = $n11+$n12+$n21+$n22; $i>0; $i--){
		
		//for denominator
		$count = 1;
		$count += ($n11 >= $i ? 1 : 0);
		$count += ($n12 >= $i ? 1 : 0);
		$count += ($n21 >= $i ? 1 : 0);
		$count += ($n22 >= $i ? 1 : 0);
		
		//for numerator
		$count -= ($n11 + $n21 >= $i ? 1 : 0);
		$count -= ($n11 + $n12 >= $i ? 1 : 0);
		$count -= ($n21 + $n22 >= $i ? 1 : 0);
		$count -= ($n12 + $n22 >= $i ? 1 : 0);
		if($count > 0){
			$denom = bcmul($denom, bcpow($i, $count));
		}
		else if($count < 0){
			$numer = bcmul($numer, bcpow($i, -$count));
		}
	}
	
	// the final division
	return bcdiv($numer, $denom, $scale);
}
function fishertest_fast($n11, $n21, $n12, $n22){
	// faster, but not arbitrary precision.
	$ret = 0;
	$det = $n11 * ($n12 + $n22) - $n12 * ($n11 + $n21);
	
	if($det > 0){ // upper ratio larger than lower, decrement n21/n12 for more extreme cases
		$minvalue = $n12 > $n21 ? $n21 : $n12;
		for(; $minvalue >= 0; $n11++, $n21--, $n12--, $n22++, $minvalue--){
			$ret += fisher_probability_fast($n11, $n21, $n12, $n22);
		}
	}
	else{
		$minvalue = $n11 > $n22 ? $n22 : $n11;
		for(; $minvalue >= 0; $n11--, $n21++, $n12++, $n22--, $minvalue--){
			$ret += fisher_probability_fast($n11, $n21, $n12, $n22);
		}
	}
	
	return $ret;
}

function fisher_probability_fast($n11, $n21, $n12, $n22){
	$lnprob = lnfact($n11 + $n21) + lnfact($n11 + $n12) + lnfact($n12 + $n22) + lnfact($n21 + $n22)
	-lnfact($n11 + $n21 + $n12 + $n22) - lnfact($n11) - lnfact($n21) - lnfact($n12) - lnfact($n22);
	return exp($lnprob);
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