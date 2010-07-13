<?php
require_once('plot.php');
require_once('plottingfunctions.php');
require_once('util.php');

$function = strtolower($_REQUEST['function']);
$action = $_REQUEST['action'];
$function = strtr($function, '-', '_'); // poisson-power to poisson_power
$breakdown = explode('_', $function, 2); //function must be <dist>(-|_)<calc>
$distribution = $breakdown[0];
$calculation = $breakdown[1];
$from = floatval($_REQUEST['from']);
$to = floatval($_REQUEST['to']);
set_time_limit(120);

// dealing with tables.

if($distribution == 'table'){
	if($calculation == 'distribution') die('invalid operation');
	$table_raw = preg_split('/[^\d\.]/', $_REQUEST['table'], -1, PREG_SPLIT_NO_EMPTY);
	
	// input is series of x,y-coordinate pairs.
	$table_count = count($table_raw);
	if($table_count < 4) die('Minimum two points required');
	for($i = 0; $i < $table_count - 1; $i+=2){
		$table[$i/2] = array(floatval($table_raw[$i]), floatval($table_raw[$i+1]));
	}
	$table_token = md5($_REQUEST['table']);
	usort($table, 'first_element_comparator');
}

// set parameters based on distribution and calculation to perform 
if($calculation == 'distribution'){
	$xtitle = 'Depth-coverage(x)';
	$ytitle = 'Proportion';
	$mean = floatval($_REQUEST['mean']);
	if($mean <= 0) $mean = 1;
	if($distribution == 'poisson'){
		$charttitle = "Depth-coverage distribution\n(Poisson, mean $mean)";
		$params = implode('_', array($function, $mean));
		$args = compact('mean', 'distribution');
	}
	else if($distribution == 'negativebinomial'){
		$size = floatval($_REQUEST['size']);
		$mean = floatval($_REQUEST['mu']);
		if($size <= 0) $size = 1;
		if($mean <= 0) $mean = 1;
		$args = compact('size', 'mean', 'distribution');
		$charttitle = "Depth-coverage distribution\n(Negative binomial, mean $mean, dispersion parameter $size)";
		$params = implode('_', array($function, $mean, $size));
	}
	
}
else if ($calculation == 'power'){
	$xtitle = 'Average number of reads';
	$ytitle = 'Power to detect variant';
	$minreads = intval($_REQUEST['minreads']);
	if($minreads <= 0) $minreads = 1;
	if($distribution == 'poisson'){
		$charttitle = "Power to detect variant\n(Poisson, minimum $minreads read".(($minreads > 1) ? 's)' : ')');
		$params = implode('_', array($function, $minreads));
		$args = compact('minreads', 'distribution');
	}
	else if($distribution == 'negativebinomial'){
		$size = floatval($_REQUEST['size']);
		if($size <= 0) $size = 1;
		$args = compact('size', 'minreads', 'distribution');
		$charttitle = "Power to detect variant\n(Negative binomial, dispersion parameter $size, minimum $minreads read".(($minreads > 1) ? 's)' : ')');
		$params = implode('_', array($function, $minreads, $size));		
	}
	else if($distribution == 'table'){
		$charttitle = 'Power to detect variant (Table)';
		$params = implode('_', array($function, $table_token));
		$args = compact('distribution', 'table');
	}	
}
else if ($calculation == 'mincarrier'){
	$xtitle = 'Number of cases tested (average depth coverage)';
	$ytitle = 'Minimum proportion of carriers';
	$minreads = intval($_REQUEST['minreads']);
	$cutoff = pow(10, intval($_REQUEST['cutoff']));
	$budget = preg_split('/[\s,]+/', $_REQUEST['budget']);
	$overhead = intval($_REQUEST['overhead']);
	$sequencecost = intval($_REQUEST['sequencecost']);
	$controls = intval($_REQUEST['controls']);
	if($minreads <= 0) $minreads = 1;
	if($cutoff <= 0 || $cutoff >= 1) $cutoff = 0.000001;
	if($controls <= 0) $controls = 400;
	if($overhead < 0) $overhead = 0;
	if($sequencecost <= 0) $sequencecost = 100;
	
	if($distribution == 'poisson'){
		$args = compact('minreads', 'cutoff', 'controls', 'distribution', 'sequencecost', 'overhead');
		$charttitle = sprintf("Minimum proportion of variant carriers required\n"
		. "(Poisson, minimum $minreads read(s), cost per sample $overhead, \n cost per genome $sequencecost, $controls controls, cutoff=%f)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $controls, $sequencecost, $overhead));		
	}
	else if ($distribution == 'negativebinomial'){
		$size = floatval($_REQUEST['size']);
		if($size <= 0) $size = 1;
		$args = compact('minreads', 'cutoff', 'controls', 'size', 'distribution', 'sequencecost', 'overhead');
		$charttitle = sprintf("Minimum proportion of variant carriers required\n"
		. "(Negative binomial, dispersion parameter $size, minimum $minreads read(s),\n".
		"cost per sample $overhead, cost per genome $sequencecost, \n$controls controls, cutoff=%f)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $controls, $size, $sequencecost, $overhead));
	}
	else if($distribution == 'table'){
		$args = compact('cutoff', 'controls', 'distribution', 'sequencecost', 'overhead', 'table');
		$charttitle = sprintf("Minimum proportion of variant carriers required\n"
		. "(Table, cost per sample $overhead, \n cost per genome $sequencecost, $controls controls, cutoff=%f)", $cutoff);
		$params = implode('_', array($function, $cutoff, 'BUDGET', $controls, $sequencecost, $overhead, $table_token));
	}
	
}
else if ($calculation == 'mincarrier_both'){
	$xtitle = 'Total number of samples tested';
	$ytitle = 'Minimum proportion of carriers';
	$minreads = intval($_REQUEST['minreads']);
	$cutoff = pow(10, intval($_REQUEST['cutoff']));
	$budget = preg_split('/[\s,]+/', $_REQUEST['budget']);
	$overhead = intval($_REQUEST['overhead']);
	$sequencecost = intval($_REQUEST['sequencecost']);
	$ratio = $_REQUEST['ratio'];
	if($ratio == 'e') $ratio = exp(1);
	else $ratio = floatval($ratio);
	
	if($ratio <= 0) $ratio = exp(1);
	if($minreads <= 0) $minreads = 1;
	if($cutoff <= 0 || $cutoff >= 1) $cutoff = 0.000001;
	if($overhead < 0) $overhead = 0;
	if($sequencecost <= 0) $sequencecost = 100;
	
	if($distribution == 'poisson'){
		$args = compact('minreads', 'cutoff', 'ratio', 'distribution', 'sequencecost', 'overhead');
		$charttitle = sprintf("Minimum proportion of variant carriers required\n"
		. "(Poisson, minimum $minreads read(s), cost per sample $overhead, \n cost per genome $sequencecost, controls/cases=$ratio, cutoff=%f)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $ratio, $sequencecost, $overhead));		
	}
	else if ($distribution == 'negativebinomial'){
		$size = floatval($_REQUEST['size']);
		if($size <= 0) $size = 1;
		$args = compact('minreads', 'cutoff', 'ratio', 'size', 'distribution', 'sequencecost', 'overhead');
		$charttitle = sprintf("Minimum proportion of variant carriers required\n"
		. "(Negative binomial, dispersion parameter $size, minimum $minreads read(s),\n".
		"cost per sample $overhead, cost per genome $sequencecost, \ncontrols/cases=$ratio, cutoff=%f)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $ratio, $size, $sequencecost, $overhead));
	}
	else if($distribution == 'table'){
		$args = compact('cutoff', 'ratio', 'distribution', 'sequencecost', 'overhead', 'table');
		$charttitle = sprintf("Minimum proportion of variant carriers required\n"
		. "(Table, cost per sample $overhead, \n cost per genome $sequencecost, controls/cases=$ratio, cutoff=%f)", $cutoff);
		$params = implode('_', array($function, $cutoff, 'BUDGET', $ratio, $sequencecost, $overhead, $table_token));		
		
	}
} 
else if ($calculation == 'power_from_case_frequency'){
	$xtitle = 'Number of cases tested (average depth coverage)';
	$ytitle = 'Power';
	$minreads = intval($_REQUEST['minreads']);
	$cutoff = pow(10, intval($_REQUEST['cutoff']));
	$budget = preg_split('/[\s,]+/', $_REQUEST['budget']);
	$overhead = intval($_REQUEST['overhead']);
	$sequencecost = intval($_REQUEST['sequencecost']);
	
	$controls = intval($_REQUEST['controls']);
	$freq = preg_split('/[\s,]+/',$_REQUEST['frequency']);
	if($minreads <= 0) $minreads = 1;
	if($cutoff <= 0 || $cutoff >= 1) $cutoff = 0.000001;
	if($controls <= 0) $controls = 400;
	if($overhead < 0) $overhead = 0;
	if($sequencecost <= 0) $sequencecost = 100;
	if($distribution == 'poisson'){
		$args = compact('minreads', 'cutoff', 'controls', 'distribution', 'sequencecost', 'overhead');
		$charttitle = sprintf("Power of experiment\n"
		. "(Poisson, minimum $minreads read(s), cost per sample $overhead, \ncost per genome $sequencecost, "
		. "$controls controls, cutoff=%f,\ncalculated from frequency of variant in cases)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $controls, $sequencecost, $overhead));
	}
	else if ($distribution == 'negativebinomial'){
		$size = floatval($_REQUEST['size']);
		if($size <= 0) $size = 1;
		$args = compact('minreads', 'cutoff', 'controls', 'size', 'distribution', 'sequencecost', 'overhead');
		$charttitle = sprintf("Power of experiment\n"
		. "(Negative binomial, dispersion parameter $size, minimum $minreads read(s), \ncost per sample $overhead, cost per genome $sequencecost, "
		. "$controls controls, \ncutoff=%f, calculated from frequency of variant in cases)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $controls, $size, $sequencecost, $overhead));
	}
	else if($distribution == 'table'){
		$args = compact('cutoff', 'controls', 'distribution', 'sequencecost', 'overhead', 'table');
		$charttitle = sprintf("Power of experiment\n"
		. "(Table, cost per sample $overhead, \ncost per genome $sequencecost, "
		. "$controls controls, cutoff=%f,\ncalculated from frequency of variant in cases)", $cutoff);
		$params = implode('_', array($function, $cutoff, 'BUDGET', $controls, $sequencecost, $overhead, $table_token));
		
	}
}
else if ($calculation == 'power_from_case_frequency_both'){
	$xtitle = 'Number of samples tested (average depth coverage)';
	$ytitle = 'Power';
	$minreads = intval($_REQUEST['minreads']);
	$cutoff = pow(10, intval($_REQUEST['cutoff']));
	$budget = preg_split('/[\s,]+/', $_REQUEST['budget']);
	$overhead = intval($_REQUEST['overhead']);
	$sequencecost = intval($_REQUEST['sequencecost']);
	$freq = preg_split('/[\s,]+/',$_REQUEST['frequency']);
	$ratio = $_REQUEST['ratio'];
	if($ratio == 'e') $ratio = exp(1);
	else $ratio = floatval($ratio);
	
	if($ratio <= 0) $ratio = exp(1);
	if($minreads <= 0) $minreads = 1;
	if($cutoff <= 0 || $cutoff >= 1) $cutoff = 0.000001;
	if($overhead < 0) $overhead = 0;
	if($sequencecost <= 0) $sequencecost = 100;
	if($distribution == 'poisson'){
		$args = compact('minreads', 'cutoff', 'ratio', 'distribution', 'sequencecost', 'overhead');
		$charttitle = sprintf("Power of experiment\n"
		. "(Poisson, minimum $minreads read(s), cost per sample $overhead, \ncost per genome $sequencecost, "
		. "controls/cases=$ratio, cutoff=%f,\ncalculated from frequency of variant in cases)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $ratio, $sequencecost, $overhead));
	}
	else if ($distribution == 'negativebinomial'){
		$size = floatval($_REQUEST['size']);
		if($size <= 0) $size = 1;
		$args = compact('minreads', 'cutoff', 'ratio', 'size', 'distribution', 'sequencecost', 'overhead');
		$charttitle = sprintf("Power of experiment\n"
		. "(Negative binomial, dispersion parameter $size, minimum $minreads read(s), \ncost per sample $overhead, cost per genome $sequencecost, "
		. "controls/cases=$ratio, \ncutoff=%f, calculated from frequency of variant in cases)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $ratio, $size, $sequencecost, $overhead));
	}
	else if($distribution == 'table'){
		$args = compact('cutoff', 'ratio', 'distribution', 'sequencecost', 'overhead', 'table');
		$charttitle = sprintf("Power of experiment\n"
		. "(Table, cost per sample $overhead, \ncost per genome $sequencecost, "
		. "controls/cases=$ratio, cutoff=%f,\ncalculated from frequency of variant in cases)", $cutoff);
		$params = implode('_', array($function, $cutoff, 'BUDGET', $ratio, $sequencecost, $overhead, $table_token));
		
	}
}
else if ($calculation == 'power_from_control_frequency'){
	$xtitle = 'Number of cases tested (average depth coverage)';
	$ytitle = 'Power';
	$minreads = intval($_REQUEST['minreads']);
	$cutoff = pow(10, intval($_REQUEST['cutoff']));
	$budget = preg_split('/[\s,]+/', $_REQUEST['budget']);
	$overhead = intval($_REQUEST['overhead']);
	$sequencecost = intval($_REQUEST['sequencecost']);
	$controls = intval($_REQUEST['controls']);
	$freq = preg_split('/[\s,]+/',$_REQUEST['frequency']);
	$oddsratio = $_REQUEST['oddsratio'];
	if($minreads <= 0) $minreads = 1;
	if($cutoff <= 0 || $cutoff >= 1) $cutoff = 0.000001;
	if($controls <= 0) $controls = 400;
	if($overhead < 0) $overhead = 0;
	if($sequencecost <= 0) $sequencecost = 100;
	if($distribution == 'poisson'){
		$args = compact('minreads', 'cutoff', 'controls', 'distribution', 'oddsratio', 'sequencecost', 'overhead');
		$charttitle = sprintf("Power of experiment\n"
		. "(Poisson, minimum $minreads read(s), cost per sample $overhead, \ncost per genome $sequencecost, "
		. "$controls controls, cutoff=%f,\ncalculated from frequency of variant in controls, odds ratio $oddsratio)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $controls, $oddsratio, $sequencecost, $overhead));
	}
	else if ($distribution == 'negativebinomial'){
		$size = floatval($_REQUEST['size']);
		if($size <= 0) $size = 1;
		$args = compact('minreads', 'cutoff', 'controls', 'size', 'distribution', 'oddsratio', 'sequencecost', 'overhead');
		$charttitle = sprintf("Power of experiment\n"
		. "(Negative binomial, dispersion parameter $size, minimum $minreads read(s), \ncost per sample $overhead, cost per genome $sequencecost,"
		. " \n$controls controls, cutoff=%f,\ncalculated from frequency of variant in controls, odds ratio $oddsratio)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $controls, $size, $oddsratio, $sequencecost, $overhead));
	}
	else if($distribution == 'table'){
			$args = compact('cutoff', 'controls', 'distribution', 'oddsratio', 'sequencecost', 'overhead', 'table');
		$charttitle = sprintf("Power of experiment\n"
		. "(Table, cost per sample $overhead, \ncost per genome $sequencecost, "
		. "$controls controls, cutoff=%f,\ncalculated from frequency of variant in controls, odds ratio $oddsratio)", $cutoff);
		$params = implode('_', array($function, $cutoff, 'BUDGET', $controls, $oddsratio, $sequencecost, $overhead, $table_token));
		
	}
}
else if ($calculation == 'power_from_control_frequency_both'){
	$xtitle = 'Number of samples tested (average depth coverage)';
	$ytitle = 'Power';
	$minreads = intval($_REQUEST['minreads']);
	$cutoff = pow(10, intval($_REQUEST['cutoff']));
	$budget = preg_split('/[\s,]+/', $_REQUEST['budget']);
	$overhead = intval($_REQUEST['overhead']);
	$sequencecost = intval($_REQUEST['sequencecost']);
	$freq = preg_split('/[\s,]+/',$_REQUEST['frequency']);
	$oddsratio = $_REQUEST['oddsratio'];
	$ratio = $_REQUEST['ratio'];
	if($ratio == 'e') $ratio = exp(1);
	else $ratio = floatval($ratio);
	
	if($ratio <= 0) $ratio = exp(1);
	if($minreads <= 0) $minreads = 1;
	if($cutoff <= 0 || $cutoff >= 1) $cutoff = 0.000001;
	if($overhead < 0) $overhead = 0;
	if($sequencecost <= 0) $sequencecost = 100;
	if($distribution == 'poisson'){
		$args = compact('minreads', 'cutoff', 'ratio', 'distribution', 'oddsratio', 'sequencecost', 'overhead');
		$charttitle = sprintf("Power of experiment\n"
		. "(Poisson, minimum $minreads read(s), cost per sample $overhead, \ncost per genome $sequencecost, "
		. "controls/cases=$ratio, cutoff=%f,\ncalculated from frequency of variant in controls, odds ratio $oddsratio)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $ratio, $oddsratio, $sequencecost, $overhead));
	}
	else if ($distribution == 'negativebinomial'){
		$size = floatval($_REQUEST['size']);
		if($size <= 0) $size = 1;
		$args = compact('minreads', 'cutoff', 'ratio', 'size', 'distribution', 'oddsratio', 'sequencecost', 'overhead');
		$charttitle = sprintf("Power of experiment\n"
		. "(Negative binomial, dispersion parameter $size, minimum $minreads read(s), \ncost per sample $overhead, cost per genome $sequencecost,"
		. " \ncontrols/cases=$ratio, cutoff=%f,\ncalculated from frequency of variant in controls, odds ratio $oddsratio)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $ratio, $size, $oddsratio, $sequencecost, $overhead));
	}
	else if($distribution == 'table'){
		$args = compact('cutoff', 'ratio', 'distribution', 'oddsratio', 'sequencecost', 'overhead', 'table');
		$charttitle = sprintf("Power of experiment\n"
		. "(Table, cost per sample $overhead, \ncost per genome $sequencecost, "
		. "controls/cases=$ratio, cutoff=%f,\ncalculated from frequency of variant in controls, odds ratio $oddsratio)", $cutoff);
		$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $ratio, $oddsratio, $sequencecost, $overhead, $table_token));
		
		
	}
}
$function = $calculation;

if($calculation == 'distribution'){
	$xstart = intval($from > 0 ? $from : 0);
	$xend = intval($to > 0 ? $to : PLOT_RANGE_AUTO);
	$data = getdata($params, $function, $args, $xstart, $xend, 1);
}
else if($calculation == 'power'){
	$xstart = floatval($from > 1 ? $from : 1);
	$xend = floatval($to > 1 ? $to : PLOT_RANGE_AUTO);
	$data = getdata($params, $function, $args, $xstart, $xend, 0.25);
}
else if($calculation == 'mincarrier' || $calculation == 'mincarrier_both'){
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
else if($calculation == 'power_from_case_frequency' 
	|| $calculation == 'power_from_control_frequency' 
	|| $calculation == 'power_from_case_frequency_both' 
	|| $calculation == 'power_from_control_frequency_both'){
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
	header('Content-type:text/plain');
	if( $calculation == 'mincarrier'
	|| $calculation == 'mincarrier_both'
	|| $calculation == 'power_from_case_frequency' 
	|| $calculation == 'power_from_control_frequency' 
	|| $calculation == 'power_from_case_frequency_both' 
	|| $calculation == 'power_from_control_frequency_both'){
		$xtitle = substr($xtitle, 0, strpos($xtitle, '('));
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
	
	switch($calculation){
		case 'distribution':
			plot(PLOT_HISTOGRAM, $data, $width, $height, $xtitle, $ytitle, $charttitle);
			break;
		case 'power':
			plot(PLOT_SCATTER, $data, $width, $height, $xtitle, $ytitle, $charttitle);
			break;
		case 'mincarrier':
		case 'mincarrier_both':
			plot(PLOT_SCATTER_MULTIPLE, $data, $width, $height, $xtitle, $ytitle, $charttitle, PLOT_AXIS_AUTO, PLOT_AXIS_AUTO, array(new DepthCoverageFormatter($budget, $sequencecost, $overhead), 'format'));
			break;
		case 'power_from_case_frequency': 
		case 'power_from_control_frequency':
		case 'power_from_case_frequency_both':
		case 'power_from_control_frequency_both':
			plot(PLOT_SCATTER_MULTIPLE, $data, $width, $height, $xtitle, $ytitle, $charttitle, PLOT_AXIS_AUTO, PLOT_AXIS_AUTO, array(new DepthCoverageFormatter($budget, $sequencecost, $overhead), 'format'));
			break;
	}
}

?>