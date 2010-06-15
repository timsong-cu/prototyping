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
	$available = $budget - $overhead * $count;
	if($available <= 0) return PLOT_DISCARD;
	$mean = $available / $sequencecost / $count;
	$mean /= 2; // diploid cell
	$power = power($args, $mean);
	$mincount = get_mincount($controls, $count, 0, $cutoff);
	if($mincount == -1) return PLOT_DISCARD;
	$ret = $mincount / ($power * $count);
	if($ret > 1) return PLOT_DISCARD;
	else return $ret;
	
}

function power_from_case_frequency($args, $count){
	extract($args);
	//var_dump($args);
	$available = $budget - $overhead * $count;
	if($available <= 0) return PLOT_DISCARD;
	$mean = $available / $sequencecost / $count;
	$mean /= 2; // Diploid cell
	$power = power($args, $mean);
	$mincount = get_mincount($controls, $count, 0, $cutoff);
	if($mincount == -1) return PLOT_DISCARD;
	$ret = 1 - binomial_cdf($count, $frequency * $power, $mincount);
	return $ret >= 0? $ret : 0;
}

function power_from_control_frequency($args, $count){
	extract($args);
	$available = $budget - $overhead * $count;
	if($available <= 0) return PLOT_DISCARD;
	$mean = $available / $sequencecost / $count;
	$mean /= 2; // Diploid cell
	$freq_cases = ($oddsratio * $frequency / (1-$frequency)) / (1+($oddsratio * $frequency / (1-$frequency)));
	$power = power($args, $mean);
	$mincount = get_mincount($controls, $count, 0, $cutoff);
	if($mincount == -1) return PLOT_DISCARD;
	$ret = 0;
	$limit = $controls * $frequency + sqrt($controls * $frequency * (1 - $frequncy)) * 5;
	$expected = $controls * $frequency;
	for($i = 0; $i < 1000 && $i < $limit; $i++){
		$mincount = get_mincount($controls, $count, $i, $cutoff);
		if($mincount == -1) break;
		$powi = 1 - binomial_cdf($count, $freq_cases * $power, $mincount);
		$probi = binomial_pmf($controls, $frequency, $i);
		$ret += $powi * $probi;
		if($i > $expected && $probi < 0.001) break;
	}
	return $ret >= 0? $ret : 0;
}

?>