<?php
require_once('plotbase.php');
require_once('plottingfunctions.php');
require_once('util.php');

function get_plot_data(){
	$function = strtolower($_REQUEST['function']);
	$function = strtr($function, '-', '_'); // poisson-power to poisson_power
	$breakdown = explode('_', $function, 2); //function must be <dist>(-|_)<calc>
	$distribution = $breakdown[0];
	$calculation = $breakdown[1];
	$xfrom = floatval($_REQUEST['xfrom']);
	$xto = floatval($_REQUEST['xto']);
	
	$yfrom = $_REQUEST['yfrom'];
	if(!$yfrom || $yfrom == 'auto') $yfrom = PLOT_AXIS_AUTO;
	else $yfrom = (floatval($yfrom) > 0 ?  floatval($yfrom) : 0);
	
	$yto = $_REQUEST['yto'];
	if(!$yto || $yto == 'auto') $yto = PLOT_AXIS_AUTO;
	else {
		$val_min = ($yfrom == PLOT_AXIS_AUTO ? 0 : $yfrom); 
		$val = floatval($yto);
		if($val <= $val_min) $yto = PLOT_AXIS_AUTO;
		else if ($val > 1) $yto = 1;
		else $yto = $val; 
	}
	
	if($calculation == 'power'){
		$step = floatval($_REQUEST['step']);
		if($step <= 0) $step = 0.25;
	}
	else{
		$step = intval($_REQUEST['step']);
		if($step <= 0) $step = 1;
	}
	
	set_time_limit(120);
	
	// Extract parameters; set all non-specified params to default values.
	$mean = floatval($_REQUEST['mean']);
	$size = floatval($_REQUEST['size']);	// dispersion parameter
	$minreads = intval($_REQUEST['minreads']);
	$cutoff = pow(10, intval($_REQUEST['cutoff']));
	$budget = preg_split('/[\s,]+/', strval($_REQUEST['budget']));
	$overhead = floatval($_REQUEST['overhead']);
	$sequencecost = floatval($_REQUEST['sequencecost']);
	$controls = intval($_REQUEST['controls']);
	$ratio = $_REQUEST['ratio'];
	if($ratio == 'e') $ratio = exp(1);
	else $ratio = floatval($ratio);
	$freq = preg_split('/[\s,]+/', strval($_REQUEST['frequency']));
	$oddsratio = floatval($_REQUEST['oddsratio']);
	
	if($mean <= 0) $mean = 1;
	if($size <= 0) $size = 5;
	if($minreads <= 0) $minreads = 2;
	if($cutoff <= 0 || $cutoff >= 1) $cutoff = 0.000001;
	if($controls <= 0) $controls = 400;
	if($overhead < 0) $overhead = 0;
	if($sequencecost <= 0) $sequencecost = 100;
	if($ratio <= 0) $ratio = exp(1);
	if($oddsratio <= 0) $oddsratio = 5;
	
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
		if($distribution == 'poisson'){
			$charttitle = "Depth-coverage distribution\n(Poisson, mean $mean)";
			$params = implode('_', array($function, $mean));
			$args = compact('mean', 'distribution');
		}
		else if($distribution == 'negativebinomial'){
			$args = compact('size', 'mean', 'distribution');
			$charttitle = "Depth-coverage distribution\n(Negative binomial, mean $mean, dispersion parameter $size)";
			$params = implode('_', array($function, $mean, $size));
		}
	}
	else if ($calculation == 'power'){
		$xtitle = 'Average number of reads';
		$ytitle = 'Power to detect variant';
		if($distribution == 'poisson'){
			$charttitle = "Power to detect variant\n(Poisson, minimum $minreads read".(($minreads > 1) ? 's)' : ')');
			$params = implode('_', array($function, $minreads));
			$args = compact('minreads', 'distribution');
		}
		else if($distribution == 'negativebinomial'){
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
		if($distribution == 'poisson'){
			$args = compact('minreads', 'cutoff', 'controls', 'distribution', 'sequencecost', 'overhead');
			$charttitle = sprintf("Minimum proportion of variant carriers required\n"
			. "(Poisson, minimum $minreads read(s), cost per sample $overhead, \n cost per genome $sequencecost, $controls controls, cutoff=%f)", $cutoff);
			$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $controls, $sequencecost, $overhead));		
		}
		else if ($distribution == 'negativebinomial'){
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
		
		if($distribution == 'poisson'){
			$args = compact('minreads', 'cutoff', 'ratio', 'distribution', 'sequencecost', 'overhead');
			$charttitle = sprintf("Minimum proportion of variant carriers required\n"
			. "(Poisson, minimum $minreads read(s), cost per sample $overhead, \n cost per genome $sequencecost, samples/cases=$ratio, cutoff=%f)", $cutoff);
			$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $ratio, $sequencecost, $overhead));		
		}
		else if ($distribution == 'negativebinomial'){
			$args = compact('minreads', 'cutoff', 'ratio', 'size', 'distribution', 'sequencecost', 'overhead');
			$charttitle = sprintf("Minimum proportion of variant carriers required\n"
			. "(Negative binomial, dispersion parameter $size, minimum $minreads read(s),\n".
			"cost per sample $overhead, cost per genome $sequencecost, \nsamples/cases=$ratio, cutoff=%f)", $cutoff);
			$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $ratio, $size, $sequencecost, $overhead));
		}
		else if($distribution == 'table'){
			$args = compact('cutoff', 'ratio', 'distribution', 'sequencecost', 'overhead', 'table');
			$charttitle = sprintf("Minimum proportion of variant carriers required\n"
			. "(Table, cost per sample $overhead, \n cost per genome $sequencecost, samples/cases=$ratio, cutoff=%f)", $cutoff);
			$params = implode('_', array($function, $cutoff, 'BUDGET', $ratio, $sequencecost, $overhead, $table_token));		
			
		}
	} 
	else if ($calculation == 'power_from_case_frequency'){
		$xtitle = 'Number of cases tested (average depth coverage)';
		$ytitle = 'Power';
	
		if($distribution == 'poisson'){
			$args = compact('minreads', 'cutoff', 'controls', 'distribution', 'sequencecost', 'overhead');
			$charttitle = sprintf("Power of experiment\n"
			. "(Poisson, minimum $minreads read(s), cost per sample $overhead, \ncost per genome $sequencecost, "
			. "$controls controls, cutoff=%f,\ncalculated from frequency of variant in cases)", $cutoff);
			$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $controls, $sequencecost, $overhead));
		}
		else if ($distribution == 'negativebinomial'){
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
		if($distribution == 'poisson'){
			$args = compact('minreads', 'cutoff', 'ratio', 'distribution', 'sequencecost', 'overhead');
			$charttitle = sprintf("Power of experiment\n"
			. "(Poisson, minimum $minreads read(s), cost per sample $overhead, \ncost per genome $sequencecost, "
			. "samples/cases=$ratio, cutoff=%f,\ncalculated from frequency of variant in cases)", $cutoff);
			$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $ratio, $sequencecost, $overhead));
		}
		else if ($distribution == 'negativebinomial'){
			$args = compact('minreads', 'cutoff', 'ratio', 'size', 'distribution', 'sequencecost', 'overhead');
			$charttitle = sprintf("Power of experiment\n"
			. "(Negative binomial, dispersion parameter $size, minimum $minreads read(s), \ncost per sample $overhead, cost per genome $sequencecost, "
			. "samples/cases=$ratio, \ncutoff=%f, calculated from frequency of variant in cases)", $cutoff);
			$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $ratio, $size, $sequencecost, $overhead));
		}
		else if($distribution == 'table'){
			$args = compact('cutoff', 'ratio', 'distribution', 'sequencecost', 'overhead', 'table');
			$charttitle = sprintf("Power of experiment\n"
			. "(Table, cost per sample $overhead, \ncost per genome $sequencecost, "
			. "samples/cases=$ratio, cutoff=%f,\ncalculated from frequency of variant in cases)", $cutoff);
			$params = implode('_', array($function, $cutoff, 'BUDGET', $ratio, $sequencecost, $overhead, $table_token));
			
		}
	}
	else if ($calculation == 'power_from_control_frequency'){
		$xtitle = 'Number of cases tested (average depth coverage)';
		$ytitle = 'Power';
		if($distribution == 'poisson'){
			$args = compact('minreads', 'cutoff', 'controls', 'distribution', 'oddsratio', 'sequencecost', 'overhead');
			$charttitle = sprintf("Power of experiment\n"
			. "(Poisson, minimum $minreads read(s), cost per sample $overhead, \ncost per genome $sequencecost, "
			. "$controls controls, cutoff=%f,\ncalculated from frequency of variant in controls, odds ratio $oddsratio)", $cutoff);
			$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $controls, $oddsratio, $sequencecost, $overhead));
		}
		else if ($distribution == 'negativebinomial'){
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
		if($distribution == 'poisson'){
			$args = compact('minreads', 'cutoff', 'ratio', 'distribution', 'oddsratio', 'sequencecost', 'overhead');
			$charttitle = sprintf("Power of experiment\n"
			. "(Poisson, minimum $minreads read(s), cost per sample $overhead, \ncost per genome $sequencecost, "
			. "samples/cases=$ratio, cutoff=%f,\ncalculated from frequency of variant in controls, odds ratio $oddsratio)", $cutoff);
			$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $ratio, $oddsratio, $sequencecost, $overhead));
		}
		else if ($distribution == 'negativebinomial'){
			$args = compact('minreads', 'cutoff', 'ratio', 'size', 'distribution', 'oddsratio', 'sequencecost', 'overhead');
			$charttitle = sprintf("Power of experiment\n"
			. "(Negative binomial, dispersion parameter $size, minimum $minreads read(s), \ncost per sample $overhead, cost per genome $sequencecost,"
			. " \nsamples/cases=$ratio, cutoff=%f,\ncalculated from frequency of variant in controls, odds ratio $oddsratio)", $cutoff);
			$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $ratio, $size, $oddsratio, $sequencecost, $overhead));
		}
		else if($distribution == 'table'){
			$args = compact('cutoff', 'ratio', 'distribution', 'oddsratio', 'sequencecost', 'overhead', 'table');
			$charttitle = sprintf("Power of experiment\n"
			. "(Table, cost per sample $overhead, \ncost per genome $sequencecost, "
			. "samples/cases=$ratio, cutoff=%f,\ncalculated from frequency of variant in controls, odds ratio $oddsratio)", $cutoff);
			$params = implode('_', array($function, $minreads, $cutoff, 'BUDGET', $ratio, $oddsratio, $sequencecost, $overhead, $table_token));
			
		}
	}
	$function = $calculation;
	
	if($calculation == 'distribution'){
		$xstart = intval($xfrom > 0 ? $xfrom : 0);
		$xend = intval($xto > 0 ? $xto : PLOT_RANGE_AUTO);
		$data = getdata($params, $function, $args, $xstart, $xend, $step);
	}
	else if($calculation == 'power'){
		$xstart = floatval($xfrom >= 0 ? $xfrom : 0);
		$xend = floatval($xto > $xfrom && $xto > 0 ? $xto : PLOT_RANGE_AUTO);
		$data = getdata($params, $function, $args, $xstart, $xend, $step);
	}
	else if($calculation == 'mincarrier' || $calculation == 'mincarrier_both'){
		$xstart = intval($xfrom > 1 ? $xfrom : 1);
		foreach($budget as $budg){
			$budg = intval($budg);
			if($budg < 0) continue;
			if($overhead > 0)
				$xend = intval($xto >= 1 && $xto <= $budg / $overhead ? $xto : PLOT_RANGE_AUTO);
			else
				$xend = intval($xto >= 1 && $xto <= $budg / $sequencecost ? $xto : PLOT_RANGE_AUTO);
			$args['budget'] = $budg;
			$para = str_replace('BUDGET', strval($budg), $params);
			$data_t = getdata($para, $function, $args, $xstart, $xend, $step);
			$data[] = array('y' => $data_t[0], 'x' => $data_t[1], 'legend' => "budget = $budg");
		}
	}
	else if($calculation == 'power_from_case_frequency' 
		|| $calculation == 'power_from_control_frequency' 
		|| $calculation == 'power_from_case_frequency_both' 
		|| $calculation == 'power_from_control_frequency_both'){
		$xstart = intval($xfrom > 1 ? $xfrom : 1);
		foreach($freq as $frequency){
			$frequency = floatval($frequency);
			if($frequency > 1 || $frequency < 0) continue;
			$args['frequency'] = $frequency;
			$para1 = $params . '_' . $frequency;
			foreach($budget as $budg){
				$budg = floatval($budg);
				if($budg < 0) continue;
				if($overhead > 0)
					$xend = intval($xto >= 1 && $xto <= $budg / $overhead ? $xto : PLOT_RANGE_AUTO);
				else
					$xend = intval($xto >= 1 && $xto <= $budg / $sequencecost ? $xto : PLOT_RANGE_AUTO);
				$args['budget'] = $budg;
				$para = str_replace('BUDGET', strval($budg), $para1);
				$data_t = getdata($para, $function, $args, $xstart, $xend, $step);
				$data[] = array('y' => $data_t[0], 'x' => $data_t[1], 'legend' => "f = $frequency, budget = $budg");
			}
		}
	}
	$ret = compact('calculation', 'data', 'xtitle', 'ytitle', 'charttitle', 'yfrom', 'yto');
	$ret['formatter'] = new DepthCoverageFormatter($budget, $sequencecost, $overhead);
	return $ret; 
}

function print_data(){
	extract(get_plot_data());
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

function plot_data(){
	extract(get_plot_data());
	// get width & height; def. 700.
	$width = intval($_REQUEST['width']);
	if(!$width) $width = 700;
	$height = intval($_REQUEST['height']);
	if(!$height) $height = 700;
	
	switch($calculation){
		case 'distribution':
			plot(PLOT_HISTOGRAM, $data, $width, $height, $xtitle, $ytitle, $charttitle, $yfrom, $yto);
			break;
		case 'power':
			plot(PLOT_SCATTER, $data, $width, $height, $xtitle, $ytitle, $charttitle, $yfrom, $yto);
			break;
		case 'mincarrier':
		case 'mincarrier_both':
			plot(PLOT_SCATTER_MULTIPLE, $data, $width, $height, $xtitle, $ytitle, $charttitle, $yfrom, $yto, array($formatter, 'format'));
			break;
		case 'power_from_case_frequency': 
		case 'power_from_control_frequency':
		case 'power_from_case_frequency_both':
		case 'power_from_control_frequency_both':
			plot(PLOT_SCATTER_MULTIPLE, $data, $width, $height, $xtitle, $ytitle, $charttitle, $yfrom, $yto, array($formatter, 'format'));
			break;
	}
}

?>