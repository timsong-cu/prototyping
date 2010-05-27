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
	$minvalue = min(array($n11, $n21, $n12, $n22));
	if($minvalue == $n11 || $minvalue == $n22){
		for(; $n11 >= 0 && $n22 >= 0; $n11--, $n21++, $n12++, $n22--){
			$ret = bcadd($ret, fisher_probability($n11, $n21, $n12, $n22, $scale), $scale);
		}
	}
	else if($minvalue == $n21 || $minvalue == $n12){
		for(; $n21 >= 0 && $n12 >= 0; $n11++, $n21--, $n12--, $n22++){
			$ret = bcadd($ret, fisher_probability($n11, $n21, $n12, $n22, $scale), $scale);
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
?>