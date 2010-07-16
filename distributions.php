<?php
require_once('util.php');

function poisson_distribution($args, $x){
	extract($args);
	$x = intval($x);
	// calculate lnprob first.
	$lnprob = log($mean) * $x - $mean - lnfact($x);
	return exp($lnprob);
}

function negativebinomial_distribution($args, $x){
	extract($args);
	$prob = $size / ($size + $mean);
	
	// pmf = (Gamma(size + x) / Gamma(size) / x!) * p^size * (1-p)^x
	// lnpmf = lngamma(size + x) - lngamma(size) - lngamma(x+1)  + size * ln(p) + x* ln(1-p)
	
	$lnpmf = lngamma($size + $x) - lngamma($size) - lnfact($x) + $size * log($prob) + $x * log(1 - $prob);
	return exp($lnpmf);
}

?>