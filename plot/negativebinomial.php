<?php 
require_once('util.php');
require_once('../math.php');

function negativebinomial_distribution($args, $x){
	$size = $args['size'];
	$mu = $args['mu'];
	
	$prob = $size / ($size + $mu);
	
	// pmf = (Gamma(size + x) / Gamma(size) / x!) * p^size * (1-p)^x
	// lnpmf = lngamma(size + x) - lngamma(size) - lngamma(x+1)  + size * ln(p) + x* ln(1-p)
	
	$lnpmf = lngamma($size + $x) - lngamma($size) - lnfact($x) + $size * log($prob) + $x * log(1-$prob);
	return exp($lnpmf);
}

function negativebinomial_power($args, $mu){
	// constant dispersion parameter
	$minreads = $args['minreads'];
	$size = $args['size'];
	$ret = 1.0;
	for($i = 0; $i < $minreads; $i++){
		$ret -= negativebinomial_distribution(array('size' => $size, 'mu' => $mu), $i);
	}
	return $ret;
}

function negativebinomial_design($args, $count){
	$minreads = $args['minreads'];
	$controls = $args['controls'];
	$budget = $args['budget'];
	$cutoff = $args['cutoff'];
	$size = $args['size'];
	$mu = $budget / $count;
	$mu /= 2; // diploid cell
	$power = negativebinomial_power(array('size' => $size, 'minreads' => $minreads), $mu);
	$mincount = get_mincount($controls, $count, $cutoff);
	if($mincount == -1) return PLOT_DISCARD;
	$ret = $mincount / ($power * $count);
	if($ret > 1) return PLOT_DISCARD;
	else return $ret;
	
}
?>