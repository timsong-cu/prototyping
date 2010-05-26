<?php
require_once('plot.php');
require_once('../bcmathext.php');

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
	}
	
}
else {
	$xtitle = 'Average number of reads';
	$ytitle = 'Power to detect variant';
	if($distribution == "poisson"){
		$args = intval($_REQUEST['minreads']);
		if($args <= 0) $args = 1;
		$charttitle = "Power to detect variant\n(Poisson, minimum $args read".(($args > 1) ? "s)" : ")");
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
	}	
}

if($action == 'data'){
	if($calculation == "distribution"){
		$data = getdata($function, $args, 0, PLOT_RANGE_AUTO, 1);
	}
	else if($calculation == "power"){
		$data = getdata($function, $args, 1, PLOT_RANGE_AUTO, 0.25);
	}
	$datax = $data[1];
	$datay = $data[0];
	header("Content-type:text/plain");
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
		plot(PLOT_HISTOGRAM, $function, $args, $width, $height, $xtitle, $ytitle, $charttitle);
	}
	else if($calculation == "power"){
		plot(PLOT_SCATTER, $function, $args, $width, $height, $xtitle, $ytitle, $charttitle, 1, PLOT_RANGE_AUTO, 0.25);
	}
}

function poisson_distribution($lambda, $x){
	$x = intval($x);
	// exp(x) is bad for ~x > 35; we do it in steps instead.
	if($lambda <= 35) 
		return floatval(bcdiv(bcpow($lambda, $x, 15), bcmul(exp($lambda), bcfact($x), 15), 15));
	else{
		$ret = bcdiv(bcpow($lambda, $x, 15), bcmul(exp(35), bcfact($x), 15), 15);
		$lambda -= 35;
		while($lambda > 35){
			$ret = bcdiv($ret, exp(35), 15);
			$lambda -= 35;
		}
		$ret = bcdiv($ret, exp($lambda), 15);
		return floatval($ret);
	}
}

function poisson_power($minreads, $lambda){
	$ret = 1.0;
	for($i = 0; $i < $minreads; $i++)
		$ret -= poisson_distribution($lambda, $i);
	return $ret;
}

function negativebinomial_distribution($args, $x){
	$size = $args['size'];
	$mu = $args['mu'];
	
	$prob_inv = ($size + $mu) / $size; //use inverse to minimize error
	$cprob_inv = ($size + $mu) / $mu;
	
	// pmf = (Gamma(size + x) / Gamma(size) / x!) * p^size * (1-p)^x
	
	$ret = "1.0";
	for($i = 0; $i < $x; $i++){
		$ret = bcmul($ret, $size+$i, 15);
	}
	
	$denom = bcmul(bcpow($prob_inv, $size, 15), bcpow($cprob_inv, $x, 15), 15);
	$denom = bcmul($denom, bcfact($x), 15);
	$ret = bcdiv($ret, $denom, 15);
	return floatval($ret);
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
?>