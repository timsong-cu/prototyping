<?php 
require_once('util.php');
require_once('../math.php');
require_once('distributions.php');

function distribution($args, $x){
	extract($args);
	$distribution .= "_distribution";
	return $distribution($args, $x);
}


function power($args, $mean){
	// constant dispersion parameter
	extract($args);
	$ret = 1.0;
	$args['mean'] = $mean;
	$distribution .= '_distribution';
	for($i = 0; $i < $minreads; $i++){
		$ret -= $distribution($args, $i);
	}
	return $ret;
}

function mincarrier($args, $count){
	extract($args);
	$mean = $budget / $count;
	$mean /= 2; // diploid cell
	$power = power($args, $mean);
	$mincount = get_mincount($controls, $count, $cutoff);
	if($mincount == -1) return PLOT_DISCARD;
	$ret = $mincount / ($power * $count);
	if($ret > 1) return PLOT_DISCARD;
	else return $ret;
	
}

function power_from_case_frequency($args, $count){
	extract($args);
	$mean = $budget / $count;
	$mean /= 2; // Diploid cell
	$power = power($args, $mean);
	$mincount = get_mincount($controls, $count, $cutoff);
	if($mincount == -1) return PLOT_DISCARD;
	$ret = 1 - binomial_cdf($count, $frequency * $power, $mincount);
	return $ret >= 0? $ret : 0;
}
?>