<?php
require_once('../math.php');
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

?>