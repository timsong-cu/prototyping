<?php
require_once('util.php');
require_once('../math.php');

function poisson_distribution($lambda, $x){
	$x = intval($x);
	// calculate lnprob first.
	$lnprob = log($lambda) * $x - $lambda - lnfact($x);
	return exp($lnprob);
}

function poisson_power($minreads, $lambda){
	$ret = 1.0;
	for($i = 0; $i < $minreads; $i++)
		$ret -= poisson_distribution($lambda, $i);
	return $ret;
}

function poisson_design($args, $count){
	$minreads = $args['minreads'];
	$controls = $args['controls'];
	$budget = $args['budget'];
	$cutoff = $args['cutoff'];
	$lambda = $budget / $count;
	$lambda /= 2; // Diploid cell
	$power = poisson_power($minreads, $lambda);
	$mincount = get_mincount($controls, $count, $cutoff);
	if($mincount == -1) return PLOT_DISCARD;
	$ret = $mincount / ($power * $count);
	if($ret > 1) return PLOT_DISCARD;
	else return $ret;
}
?>