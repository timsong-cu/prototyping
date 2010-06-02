<?php
require_once('plot.php');
require_once('poisson.php');
require_once('negativebinomial.php');
require_once('util.php');

$function = strtolower($_REQUEST['function']);
$action = $_REQUEST['action'];
$function = strtr($function, "-", "_"); // poisson-power to poisson_power
$breakdown = explode('_', $function); //function must be <dist>(-|_)<calc>
$distribution = $breakdown[0];
$calculation = $breakdown[1];

// set parameters based on distribution and calculation to perform 
if($calculation == "distribution"){
	$xtitle = 'Depth-coverage(x)';
	$ytitle = 'Proportion';
	if($distribution == "poisson"){
		$args = floatval($_REQUEST['lambda']);
		if($args <= 0) $args = 1;
		$charttitle = "Depth-coverage distribution\n(Poisson, mean $args)";
		$params = implode('_', array($function, $args));
	}
	else if($distribution == "negativebinomial"){
		$size = floatval($_REQUEST['size']);
		$mu = floatval($_REQUEST['mu']);
		if($size <= 0) $size = 1;
		if($mu <= 0) $mu = 1;
		$args = array(
		'size' => $size,
		'mu' => $mu
		);
		$charttitle = "Depth-coverage distribution\n(Negative binomial, mean $mu, dispersion parameter $size)";
		$params = implode('_', array($function, $mu, $size));
	}
	
}
else if ($calculation == "power"){
	$xtitle = 'Average number of reads';
	$ytitle = 'Power to detect variant';
	if($distribution == "poisson"){
		$args = intval($_REQUEST['minreads']);
		if($args <= 0) $args = 1;
		$charttitle = "Power to detect variant\n(Poisson, minimum $args read".(($args > 1) ? "s)" : ")");
		$params = implode('_', array($function, $args));
	}
	else if($distribution == "negativebinomial"){
		$size = floatval($_REQUEST['size']);
		$minreads = intval($_REQUEST['minreads']);
		if($size <= 0) $size = 1;
		if($minreads <= 0) $minreads = 1;
		$args = array(
		'size' => $size,
		'minreads' => $minreads
		);
		$charttitle = "Power to detect variant\n(Negative binomial, dispersion parameter $size, minimum $minreads read".(($minreads > 1) ? "s)" : ")");
		$params = implode('_', array($function, $minreads, $size));		
	}	
}
else if ($calculation == "design"){
	$xtitle = "Number of cases tested";
	$ytitle = "Minimum proportion of carriers";
	$minreads = intval($_REQUEST['minreads']);
	$cutoff = pow(10, intval($_REQUEST['cutoff']));
	$budget = intval($_REQUEST['budget']);
	$controls = intval($_REQUEST['controls']);
	if($minreads <= 0) $minreads = 1;
	if($cutoff <= 0 || $cutoff >= 1) $cutoff = "0.000001";
	if($budget <= 0) $budget = 1000;
	if($controls <= 0) $controls = 400;
	if($distribution == "poisson"){
		$args = array(
		'minreads' => $minreads,
		'cutoff' => $cutoff,
		'budget' => $budget,
		'controls' => $controls
		);
		$charttitle = sprintf("Minimum proportion of variant carriers required\n"
		. "(Poisson, minimum $minreads read(s), budget $budget,\n $controls controls, cutoff=%f)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, $budget, $controls));		
	}
	else if ($distribution == "negativebinomial"){
		$size = floatval($_REQUEST['size']);
		if($size <= 0) $size = 1;
		$args = array(
		'minreads' => $minreads,
		'cutoff' => $cutoff,
		'budget' => $budget,
		'controls' => $controls,
		'size' => $size
		);
		$charttitle = sprintf("Minimum proportion of variant carriers required\n"
		. "(Negative binomial, dispersion parameter $size, minimum $minreads read(s),\n".
		"budget $budget, $controls controls, cutoff=%f)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, $budget, $controls, $size));
	}
} 

if($calculation == "distribution"){
	$data = getdata($params, $function, $args, 0, PLOT_RANGE_AUTO, 1);
}
else if($calculation == "power"){
	$data = getdata($params, $function, $args, 1, PLOT_RANGE_AUTO, 0.25);
}
else if($calculation == 'design'){
	$data = getdata($params, $function, $args, 1, $budget, 1);
}
if($action == 'data'){
	header("Content-type:text/plain");
	$datax = $data[1];
	$datay = $data[0];
	echo $charttitle."\n".$xtitle."\t".$ytitle."\n";
	$count = count($datax);
	for($i = 0; $i < $count; $i++){
		echo $datax[$i]."\t".$datay[$i]."\n";
	}
}
else { //plotting is default
	// get width & height; def. 700.
	$width = $_REQUEST['width'];
	if(!$width) $width = 700;
	$height = $_REQUEST['height'];
	if(!$height) $height = 700;
	
	if($calculation == "distribution"){
		plot(PLOT_HISTOGRAM, $data, $width, $height, $xtitle, $ytitle, $charttitle);
	}
	else if($calculation == "power"){
		plot(PLOT_SCATTER, $data, $width, $height, $xtitle, $ytitle, $charttitle);
	}
	else if($calculation == "design"){
		plot(PLOT_SCATTER, $data, $width, $height, $xtitle, $ytitle, $charttitle);
	}
}

?>