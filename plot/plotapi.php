<?php
require_once('plot.php');
require_once('plottingfunctions.php');
require_once('util.php');

$function = strtolower($_REQUEST['function']);
$action = $_REQUEST['action'];
$function = strtr($function, "-", "_"); // poisson-power to poisson_power
$breakdown = explode('_', $function, 2); //function must be <dist>(-|_)<calc>
$distribution = $breakdown[0];
$calculation = $breakdown[1];
$from = floatval($_REQUEST['from']);
$to = floatval($_REQUEST['to']);

// set parameters based on distribution and calculation to perform 
if($calculation == "distribution"){
	$xtitle = 'Depth-coverage(x)';
	$ytitle = 'Proportion';
	if($distribution == "poisson"){
		$mean = floatval($_REQUEST['lambda']);
		if($mean <= 0) $mean = 1;
		$charttitle = "Depth-coverage distribution\n(Poisson, mean $mean)";
		$params = implode('_', array($function, $mean));
		$args = compact('mean', 'distribution');
	}
	else if($distribution == "negativebinomial"){
		$size = floatval($_REQUEST['size']);
		$mean = floatval($_REQUEST['mu']);
		if($size <= 0) $size = 1;
		if($mean <= 0) $mean = 1;
		$args = compact('size', 'mean', 'distribution');
		$charttitle = "Depth-coverage distribution\n(Negative binomial, mean $mean, dispersion parameter $size)";
		$params = implode('_', array($function, $mean, $size));
	}
	
}
else if ($calculation == "power"){
	$xtitle = 'Average number of reads';
	$ytitle = 'Power to detect variant';
	$minreads = intval($_REQUEST['minreads']);
	if($minreads <= 0) $args = 1;
	if($distribution == "poisson"){
		$charttitle = "Power to detect variant\n(Poisson, minimum $minreads read".(($minreads > 1) ? "s)" : ")");
		$params = implode('_', array($function, $minreads));
		$args = compact('minreads', 'distribution');
	}
	else if($distribution == "negativebinomial"){
		$size = floatval($_REQUEST['size']);
		if($size <= 0) $size = 1;
		$args = compact('size', 'minreads', 'distribution');
		$charttitle = "Power to detect variant\n(Negative binomial, dispersion parameter $size, minimum $minreads read".(($minreads > 1) ? "s)" : ")");
		$params = implode('_', array($function, $minreads, $size));		
	}	
}
else if ($calculation == "mincarrier"){
	$xtitle = "Number of cases tested";
	$ytitle = "Minimum proportion of carriers";
	$minreads = intval($_REQUEST['minreads']);
	$cutoff = pow(10, intval($_REQUEST['cutoff']));
	$budget = preg_split("/[\s,]+/", $_REQUEST['budget']);
	$overhead = intval($_REQUEST['overhead']);
	$sequencecost = intval($_REQUEST['sequencecost']);
	$controls = intval($_REQUEST['controls']);
	if($minreads <= 0) $minreads = 1;
	if($cutoff <= 0 || $cutoff >= 1) $cutoff = 0.000001;
	if($controls <= 0) $controls = 400;
	if($overhead < 0) $overhead = 0;
	if($sequencecost <= 0) $sequencecost = 100;
	
	if($distribution == "poisson"){
		$args = compact('minreads', 'cutoff', 'controls', 'distribution', 'sequencecost', 'overhead');
		$charttitle = sprintf("Minimum proportion of variant carriers required\n"
		. "(Poisson, minimum $minreads read(s), cost per sample $overhead, \n cost per genome $sequencecost, $controls controls, cutoff=%f)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $controls, $sequencecost, $overhead));		
	}
	else if ($distribution == "negativebinomial"){
		$size = floatval($_REQUEST['size']);
		if($size <= 0) $size = 1;
		$args = compact('minreads', 'cutoff', 'controls', 'size', 'distribution', 'sequencecost', 'overhead');
		$charttitle = sprintf("Minimum proportion of variant carriers required\n"
		. "(Negative binomial, dispersion parameter $size, minimum $minreads read(s),\n".
		"cost per sample $overhead, cost per genome $sequencecost, \n$controls controls, cutoff=%f)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $controls, $size, $sequencecost, $overhead));
	}
	
} 
else if ($calculation == "power_from_case_frequency"){
	$xtitle = "Number of cases tested";
	$ytitle = "Power";
	$minreads = intval($_REQUEST['minreads']);
	$cutoff = pow(10, intval($_REQUEST['cutoff']));
	$budget = preg_split("/[\s,]+/", $_REQUEST['budget']);
	$overhead = intval($_REQUEST['overhead']);
	$sequencecost = intval($_REQUEST['sequencecost']);
	
	$controls = intval($_REQUEST['controls']);
	$freq = preg_split("/[\s,]+/",$_REQUEST['frequency']);
	if($minreads <= 0) $minreads = 1;
	if($cutoff <= 0 || $cutoff >= 1) $cutoff = 0.000001;
	if($controls <= 0) $controls = 400;
	if($overhead < 0) $overhead = 0;
	if($sequencecost <= 0) $sequencecost = 100;
	if($distribution == "poisson"){
		$args = compact('minreads', 'cutoff', 'controls', 'distribution', 'sequencecost', 'overhead');
		$charttitle = sprintf("Power of experiment\n"
		. "(Poisson, minimum $minreads read(s), cost per sample $overhead, \ncost per genome $sequencecost, "
		. "$controls controls, cutoff=%f,\ncalculated from frequency of variant in cases)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $controls, $sequencecost, $overhead));
	}
	else if ($distribution == "negativebinomial"){
		$size = floatval($_REQUEST['size']);
		if($size <= 0) $size = 1;
		$args = compact('minreads', 'cutoff', 'controls', 'size', 'distribution', 'sequencecost', 'overhead');
		$charttitle = sprintf("Power of experiment\n"
		. "(Negative binomial, dispersion parameter $size, minimum $minreads read(s), \ncost per sample $overhead, cost per genome $sequencecost, "
		. "$controls controls, \ncutoff=%f, calculated from frequency of variant in cases)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $controls, $size, $sequencecost, $overhead));
	}
}
else if ($calculation == "power_from_control_frequency"){
	$xtitle = "Number of cases tested";
	$ytitle = "Power";
	$minreads = intval($_REQUEST['minreads']);
	$cutoff = pow(10, intval($_REQUEST['cutoff']));
	$budget = preg_split("/[\s,]+/", $_REQUEST['budget']);
	$overhead = intval($_REQUEST['overhead']);
	$sequencecost = intval($_REQUEST['sequencecost']);
	$controls = intval($_REQUEST['controls']);
	$freq = preg_split("/[\s,]+/",$_REQUEST['frequency']);
	$oddsratio = $_REQUEST['oddsratio'];
	if($minreads <= 0) $minreads = 1;
	if($cutoff <= 0 || $cutoff >= 1) $cutoff = 0.000001;
	if($controls <= 0) $controls = 400;
	if($overhead < 0) $overhead = 0;
	if($sequencecost <= 0) $sequencecost = 100;
	if($distribution == "poisson"){
		$args = compact('minreads', 'cutoff', 'controls', 'distribution', 'oddsratio', 'sequencecost', 'overhead');
		$charttitle = sprintf("Power of experiment\n"
		. "(Poisson, minimum $minreads read(s), cost per sample $overhead, \ncost per genome $sequencecost, "
		. "$controls controls, cutoff=%f,\ncalculated from frequency of variant in controls, odds ratio $oddsratio)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $controls, $oddsratio, $sequencecost, $overhead));
	}
	else if ($distribution == "negativebinomial"){
		$size = floatval($_REQUEST['size']);
		if($size <= 0) $size = 1;
		$args = compact('minreads', 'cutoff', 'controls', 'size', 'distribution', 'oddsratio', 'sequencecost', 'overhead');
		$charttitle = sprintf("Power of experiment\n"
		. "(Negative binomial, dispersion parameter $size, minimum $minreads read(s), \ncost per sample $overhead, cost per genome $sequencecost,"
		. " \n$controls controls, cutoff=%f,\ncalculated from frequency of variant in controls, odds ratio $oddsratio)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $controls, $size, $oddsratio, $sequencecost, $overhead));
	}
}
$function = $calculation;

if($calculation == "distribution"){
	$xstart = intval($from > 0 ? $from : 0);
	$xend = intval($to > 0 ? $to : PLOT_RANGE_AUTO);
	$data = getdata($params, $function, $args, $xstart, $xend, 1);
}
else if($calculation == "power"){
	$xstart = floatval($from > 1 ? $from : 1);
	$xend = floatval($to > 1 ? $to : PLOT_RANGE_AUTO);
	$data = getdata($params, $function, $args, $xstart, $xend, 0.25);
}
else if($calculation == 'mincarrier'){
	$xstart = intval($from > 1 ? $from : 1);
	foreach($budget as $budg){
		$budg = intval($budg);
		if($budg < 0) continue;
		if($overhead > 0)
			$xend = intval($to >= 1 && $to <= $budg / $overhead ? $to : PLOT_RANGE_AUTO);
		else
			$xend = intval($to >= 1 && $to <= $budg / $sequencecost ? $to : PLOT_RANGE_AUTO);
		$args['budget'] = $budg;
		$para = str_replace('BUDGET', strval($budg), $params);
		$data_t = getdata($para, $function, $args, $xstart, $xend, 1);
		$data[] = array('y' => $data_t[0], 'x' => $data_t[1], 'legend' => "budget = $budg");
	}
}
else if($calculation == "power_from_case_frequency"){
	$xstart = intval($from > 1 ? $from : 1);
	foreach($freq as $frequency){
		$frequency = floatval($frequency);
		if($frequency > 1 || $frequency < 0) continue;
		$args['frequency'] = $frequency;
		$para1 = $params . '_' . $frequency;
		foreach($budget as $budg){
			$budg = intval($budg);
			if($budg < 0) continue;
			if($overhead > 0)
				$xend = intval($to >= 1 && $to <= $budg / $overhead ? $to : PLOT_RANGE_AUTO);
			else
				$xend = intval($to >= 1 && $to <= $budg / $sequencecost ? $to : PLOT_RANGE_AUTO);
			$args['budget'] = $budg;
			$para = str_replace('BUDGET', strval($budg), $para1);
			$data_t = getdata($para, $function, $args, $xstart, $xend, 1);
			$data[] = array('y' => $data_t[0], 'x' => $data_t[1], 'legend' => "f = $frequency, budget = $budg");
		}
	}
}
else if($calculation == "power_from_control_frequency"){
	$xstart = intval($from > 1 ? $from : 1);
	foreach($freq as $frequency){
		$frequency = floatval($frequency);
		if($frequency > 1 || $frequency < 0) continue;
		$args['frequency'] = $frequency;
		$para1 = $params . '_' . $frequency;
		foreach($budget as $budg){
			$budg = intval($budg);
			if($budg < 0) continue;
			if($overhead > 0)
				$xend = intval($to >= 1 && $to <= $budg / $overhead ? $to : PLOT_RANGE_AUTO);
			else
				$xend = intval($to >= 1 && $to <= $budg / $sequencecost ? $to : PLOT_RANGE_AUTO);
			$args['budget'] = $budg;
			$para = str_replace('BUDGET', strval($budg), $para1);
			$data_t = getdata($para, $function, $args, $xstart, $xend, 1);
			$data[] = array('y' => $data_t[0], 'x' => $data_t[1], 'legend' => "f = $frequency, budget = $budg");
		}
	}	
}
if($action == 'data'){
	header("Content-type:text/plain");
	if($calculation == "power_from_case_frequency" || $calculation == "mincarrier"|| $calculation == "power_from_control_frequency"){
		echo $charttitle."\n";
		foreach($data as $series){
			echo '(' . $series['legend']. ")\n" . $xtitle."\t".$ytitle."\n";
			$count = count($series['x']);
			for($i = 0; $i < $count; $i++){
				echo $series['x'][$i]."\t".$series['y'][$i]."\n";
			}	
		}
	}
	else{
		$datax = $data[1];
		$datay = $data[0];
		echo $charttitle."\n".$xtitle."\t".$ytitle."\n";
		$count = count($datax);
		for($i = 0; $i < $count; $i++){
			echo $datax[$i]."\t".$datay[$i]."\n";
		}
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
	else if($calculation == "mincarrier"){
		plot(PLOT_SCATTER_MULTIPLE, $data, $width, $height, $xtitle, $ytitle, $charttitle);
	}
	else if($calculation == "power_from_case_frequency" || $calculation == "power_from_control_frequency"){
		plot(PLOT_SCATTER_MULTIPLE, $data, $width, $height, $xtitle, $ytitle, $charttitle);
	}
}

?>