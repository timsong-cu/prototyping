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
 * @param int $scale the number of decimal digits after the decimal point to return, default 15.
 * @return string the p-value computed by fisher's exact test.
 */
function fishertest($n11, $n21, $n12, $n22, $scale=15){
	return bcdiv(
		bcmul(
			bcmul(
				bcfact($n11+$n21),
				bcfact($n21+$n22)
			),
			bcmul(
				bcfact($n11+$n12),
				bcfact($n21+$n22)
			)
		),
		bcmul(
			bcmul(
				bcfact($n11),
				bcfact($n12)
			),
			bcmul(
				bcmul(
					bcfact($n21),
					bcfact($n22)
				),
				bcfact($n11+$n12+$n21+$n22)
			)
		),
		$scale
	);
}

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
function fishertest_faster($n11, $n21, $n12, $n22, $scale=15){
	$denom = "1"; // the denominator
	$numer = "1"; // the numerator
	
	// Count the number of times each value is to be multiplied in the
	// numerator and denominator
	for($i = $n11+$n21; $i>0; $i--){
		$numer_count[$i]++;
	}
	for($i = $n12+$n22; $i>0; $i--){
		$numer_count[$i]++;
	}
	for($i = $n11+$n12; $i>0; $i--){
		$numer_count[$i]++;
	}
	for($i = $n21+$n22; $i>0; $i--){
		$numer_count[$i]++;
	}
	
	for($i = $n11; $i>0; $i--){
		$denom_count[$i]++;
	}
	for($i = $n12; $i>0; $i--){
		$denom_count[$i]++;
	}
	for($i = $n21; $i>0; $i--){
		$denom_count[$i]++;
	}
	for($i = $n22; $i>0; $i--){
		$denom_count[$i]++;
	}
	for($i = $n11+$n12+$n21+$n22; $i>0; $i--){
		$denom_count[$i]++;
	}
	
	/*
	 * Now run multiplication: we cancel the unnecessary multiplications
	 * when we multiply both denom. and numer. by the same number.
	 */
	for($i = $n11+$n12+$n21+$n22; $i>0; $i--){		
		if($denom_count[$i] > $numer_count[$i]){
			$denom = bcmul($denom, bcpow($i, $denom_count[$i] - $numer_count[$i]));
		}
		else if($denom_count[$i] < $numer_count[$i]){
			$numer = bcmul($numer, bcpow($i, $numer_count[$i] - $denom_count[$i]));
		}
	}
	
	// the final division
	return bcdiv($numer, $denom, $scale);
}
?>