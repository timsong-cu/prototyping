<?php

/**
 * This file contains functions used to compute individual data points in the plot.
 */ 
require_once('util.php');

define('CONTROL_FREQUENCY_DEFAULT', 0.005);

function poisson_distribution($args, $x){
	extract($args);
	return poisson_pmf($mean, $x);	
}

function negativebinomial_distribution($args, $x){
	extract($args);
	return negativebinomial_pmf($mean, $size, $x);
}
function distribution($args, $x){
	extract($args);
	$distribution .= "_distribution";
	return $distribution($args, $x);
}


function power($args, $mean){
	extract($args);
	if($mean == 0) return 0;
	if($distribution != 'table'){
		$ret = 1.0;
		$args['mean'] = $mean;
		$distribution .= '_distribution';
		for($i = 0; $i < $minreads; $i++){
			$ret -= $distribution($args, $i);
		}
		return $ret;
	}
	else{
		if(array_bsearch(array($mean), $table, 'first_element_comparator', $index)){
			return $table[$index][1];
		}
		else{		
			if($index == 0){
				$prev = $table[0][0];
				$prev_val = $table[0][1];
				$next = $table[1][0];
				$next_val = $table[1][1];
			}
			else if ($index == count($table)){
				$prev = $table[$index-2][0];
				$prev_val = $table[$index-2][1];
				$next = $table[$index-1][0];
				$next_val = $table[$index-1][1];
			}
			else {
				$prev = $table[$index-1][0];
				$prev_val = $table[$index-1][1];
				$next = $table[$index][0];
				$next_val = $table[$index][1];
			}
			$diff = $next - $prev;
			$ret = $prev_val + ($next_val- $prev_val) * ( ($mean-$prev) / $diff);
			
			if($ret > 1) $ret = 1;
			else if ($ret < 0) $ret = 0;
			
			return $ret;
			
		}
			
	}
}

function mincarrier($args, $count){
	extract($args);
	$available = $budget - $overhead * $count;
	if($available <= 0) return PLOT_DISCARD;
	$mean = $available / $sequencecost / $count;
	$power = power($args, $mean);
	$mincount = get_mincount($controls, $count, 0, $cutoff);
	if($mincount == -1 || $power <= 0) return PLOT_DISCARD;
	$mincount = ceil($mincount / $power);
	$ret = $mincount / $count;
	if($ret > 1) return PLOT_DISCARD;
	else return $ret;
	
}

function mincarrier_both($args, $count){
	extract($args);
	$available = $budget - $overhead * $count;
	if($available <= 0) return PLOT_DISCARD;
	$mean = $available / $sequencecost / $count;
	$power = power($args, $mean);
	$cases = round($count / $ratio);
	$controls = $count - $cases;
	if($cases <= 0 || $controls <= 0) return PLOT_DISCARD;
	$mincount = get_mincount($controls, $cases, 0, $cutoff);
	if($mincount == -1 || $power <= 0) return PLOT_DISCARD;
	$mincount = ceil($mincount / $power);
	$ret = $mincount / $cases;
	if($ret > 1) return PLOT_DISCARD;
	return $ret;
}

function power_from_case_frequency($args, $count){
	extract($args);
	$available = $budget - $overhead * $count;
	if($available <= 0) return PLOT_DISCARD;
	$mean = $available / $sequencecost / $count;
	$power = power($args, $mean);
	$mincount = get_mincount($controls, $count, 0, $cutoff);
	if($mincount == -1 || $power <= 0) return PLOT_DISCARD;
	if($controlcoverage == -1)
		$ret = 1 - binomial_cdf($count, $frequency * $power, $mincount);
	else{
		$power_control = power($args, $controlcoverage);
		$limit = $controls * CONTROL_FREQUENCY_DEFAULT + sqrt($controls * CONTROL_FREQUENCY_DEFAULT * (1 - CONTROL_FREQUENCY_DEFAULT)) * 5;
		$expected = $controls * CONTROL_FREQUENCY_DEFAULT;
		$totalprob = 0;
		for($i = 0; $i < 1000 && $i < $limit; $i++){ // iterate over the number of actual carriers in controls.
			$mincount = get_mincount($controls, $count, $i, $cutoff);
			if($mincount == -1) break;
			$powi = 1 - binomial_cdf($count, $frequency * $power, $mincount);
			$probi = binomial_pmf($i, $power_control, 0) * binomial_pmf($controls, CONTROL_FREQUENCY_DEFAULT, $i); 
			$totalprob += $probi;
			$ret += $powi * $probi;
			if($i > $expected && $probi < 0.0001) break;
		}
		$ret /= $totalprob; //normalize
	}
	return $ret >= 0? $ret : 0;
}

function power_from_case_frequency_both($args, $count){
	extract($args);
	$available = $budget - $overhead * $count;
	if($available <= 0) return PLOT_DISCARD;
	$mean = $available / $sequencecost / $count;
	$cases = round($count / $ratio);
	$controls = $count - $cases;
	if($cases <= 0 || $controls <= 0) return PLOT_DISCARD;
	$power = power($args, $mean);
	$mincount = get_mincount($controls, $cases, 0, $cutoff);
	if($mincount == -1 || $power <= 0) return PLOT_DISCARD;
	$limit = $controls * CONTROL_FREQUENCY_DEFAULT + sqrt($controls * CONTROL_FREQUENCY_DEFAULT * (1 - CONTROL_FREQUENCY_DEFAULT)) * 5;
	$expected = $controls * CONTROL_FREQUENCY_DEFAULT;
	$totalprob = 0;
	for($i = 0; $i < 1000 && $i < $limit; $i++){ // iterate over the number of actual carriers in controls.
		$mincount = get_mincount($controls, $cases, $i, $cutoff);
		if($mincount == -1) break;
		$powi = 1 - binomial_cdf($cases, $frequency * $power, $mincount);
		$probi = binomial_pmf($i, $power, 0) * binomial_pmf($controls, CONTROL_FREQUENCY_DEFAULT, $i); 
		$totalprob += $probi;
		$ret += $powi * $probi;
		if($i > $expected && $probi < 0.0001) break;
	}
	$ret /= $totalprob; //normalize
	return $ret >= 0? $ret : 0;
}

function power_from_control_frequency($args, $count){
	extract($args);
	$available = $budget - $overhead * $count;
	if($available <= 0) return PLOT_DISCARD;
	$mean = $available / $sequencecost / $count;
	$freq_cases = ($oddsratio * $frequency / (1-$frequency)) / (1+($oddsratio * $frequency / (1-$frequency)));
	$power = power($args, $mean);
	$mincount = get_mincount($controls, $count, 0, $cutoff);
	if($mincount == -1 || $power <= 0) return PLOT_DISCARD;
	$ret = 0;
	$limit = $controls * $frequency + sqrt($controls * $frequency * (1 - $frequency)) * 5;
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

function power_from_control_frequency_both($args, $count){
	extract($args);
	$available = $budget - $overhead * $count;
	if($available <= 0) return PLOT_DISCARD;
	$mean = $available / $sequencecost / $count;
	$cases = round($count / $ratio);
	$controls = $count - $cases;
	if($cases <= 0 || $controls <= 0) return PLOT_DISCARD;
	$freq_cases = ($oddsratio * $frequency / (1-$frequency)) / (1+($oddsratio * $frequency / (1-$frequency)));
	$power = power($args, $mean);
	$mincount = get_mincount($controls, $cases, 0, $cutoff);
	if($mincount == -1 || $power <= 0) return PLOT_DISCARD;
	$ret = 0;
	$limit = $controls * $frequency + sqrt($controls * $frequency * (1 - $frequency)) * 5;
	$expected = $controls * $frequency;
	for($i = 0; $i < 1000 && $i < $limit; $i++){
		$mincount = get_mincount($controls, $cases, $i, $cutoff);
		if($mincount == -1) break;
		$powi = 1 - binomial_cdf($cases, $freq_cases * $power, $mincount);
		$probi = binomial_pmf($controls, $frequency, $i);
		$ret += $powi * $probi;
		if($i > $expected && $probi < 0.001) break;
	}

	return $ret >= 0? $ret : 0;
}

class DepthCoverageFormatter{
	private $budget;
	private $sequencecost;
	private $overhead;
	function format($count){
		if($count == 0) return $count;
		$labels[] = strval($count);
		$maxlen = strlen($labels[0]);
		foreach($this->budget as $budget){
			$available = $budget- ($this->overhead) * $count;
			$dc = $available / ($this->sequencecost) / $count;
			if($dc < 0) $dc = 0;
			$label = sprintf("(%.2f)", $dc);
			$maxlen = strlen($label) > $maxlen ? strlen($label) : $maxlen;
			$labels[] = $label;
		}
		foreach($labels as &$label){
			$label = str_pad($label, $maxlen, ' ', STR_PAD_BOTH);
		}
		unset($label);
		return implode("\n", $labels);
	}
	function __construct($budget, $sequencecost, $overhead){
		$this->budget = $budget;
		$this->sequencecost = $sequencecost;
		$this->overhead = $overhead;		
	}
}
?>