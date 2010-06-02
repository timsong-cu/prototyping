<?php 
require_once('util.php');
require_once('../math.php');

function negativebinomial_distribution($args, $x){
	extract($args);
	$prob = $size / ($size + $mu);
	
	// pmf = (Gamma(size + x) / Gamma(size) / x!) * p^size * (1-p)^x
	// lnpmf = lngamma(size + x) - lngamma(size) - lngamma(x+1)  + size * ln(p) + x* ln(1-p)
	
	$lnpmf = lngamma($size + $x) - lngamma($size) - lnfact($x) + $size * log($prob) + $x * log(1-$prob);
	return exp($lnpmf);
}

function negativebinomial_power($args, $mu){
	// constant dispersion parameter
	extract($args);
	$ret = 1.0;
	for($i = 0; $i < $minreads; $i++){
		$ret -= negativebinomial_distribution(compact('size', 'mu'), $i);
	}
	return $ret;
}

function negativebinomial_mincarrier($args, $count){
	extract($args);
	$mu = $budget / $count;
	$mu /= 2; // diploid cell
	$power = negativebinomial_power(compact('size', 'minreads'), $mu);
	$mincount = get_mincount($controls, $count, $cutoff);
	if($mincount == -1) return PLOT_DISCARD;
	$ret = $mincount / ($power * $count);
	if($ret > 1) return PLOT_DISCARD;
	else return $ret;
	
}

function negativebinomial_power_from_case_frequency($args, $count){
	extract($args);
	$mu = $budget / $count;
	$mu /= 2; // Diploid cell
	$power = negativebinomial_power(compact('size', 'minreads'), $mu);
	$mincount = get_mincount($controls, $count, $cutoff);
	if($mincount == -1) return PLOT_DISCARD;
	$ret = 1 - binomial_cdf($count, $frequency * $power, $mincount);
	return $ret >= 0? $ret : 0;
}
?>