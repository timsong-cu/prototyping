<?php
require_once('plot.php');
require_once('../bcmathext.php');

$function = $_REQUEST['function'];

$width = $_REQUEST['width'];
if(!$width) $width = 1000;
$height = $_REQUEST['height'];
if(!$height) $height = 1000;

if($function == "poisson-distribution"){
	$xtitle = 'Number of reads';
	$ytitle = 'P(X=k)';
	$args = floatval($_REQUEST['lambda']);
	if($args <= 0) $args = 1;
	$charttitle = "Poisson distribution given an average of $args read(s)";
	$function = "poisson_distribution";
	plot(PLOT_HISTOGRAM, $function, $args, $width, $height, $xtitle, $ytitle, $charttitle);
}
else if($function == "poisson-power"){
	$xtitle = 'Average number of reads';
	$ytitle = 'Power to detect variant';
	$args = intval($_REQUEST['minreads']);
	if($args <= 0) $args = 1;
	$charttitle = "Power to detect variant given the requirement of $args read(s)";
	$function = "poisson_power";
	plot(PLOT_SCATTER, $function, $args, $width, $height, $xtitle, $ytitle, $charttitle, 1, PLOT_RANGE_AUTO, 0.25);
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
?>