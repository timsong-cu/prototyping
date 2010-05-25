<?php
require_once('../jpgraph/jpgraph.php');
require_once('../jpgraph/jpgraph_bar.php');
require_once('../jpgraph/jpgraph_line.php');
require_once('../jpgraph/jpgraph_scatter.php');

define('PLOT_HISTOGRAM', '1');
define('PLOT_LINE', '2');
define('PLOT_SCATTER', '3');
define('PLOT_RANGE_AUTO', '-1');
/**
 * Plot a function.
 * @param $type The type of the plot to generate.
 * @param $function The name of the function to plot.
 * @param $args Optional arguments to pass to the function. If null, $function(x) is called; otherwise, $function($args, x) is called.
 * @param $xstart The starting point of the plot range, default 0.
 * @param $xend The end point of the plot range. By default, plot until the curve reached a plateau. In no event will more than 500 points be plotted. 
 * @param $step Distance between two adjacent points.
 * @param $xtitle The title of the X axis.
 * @param $ytitle The title of the Y axis.
 * @param $charttitle The title of the chart.
 */

function plot($type, $function, $args = null, $width = 500, $height=500, $xtitle = "", $ytitle = "", $charttitle = "",  $xstart = 0, $xend = PLOT_RANGE_AUTO, $step = 1){
	switch($type){
		case PLOT_HISTOGRAM:
			plot_histogram($function, $args, $width, $height, $xtitle, $ytitle, $charttitle, $xstart, $xend, $step);
			break;
		case PLOT_LINE:
			plot_line($function, $args, $xtitle, $width, $height, $ytitle, $charttitle, $xstart, $xend, $step);
			break;
		case PLOT_SCATTER:
			plot_scatter($function, $args, $width, $height, $xtitle, $ytitle, $charttitle, $xstart, $xend, $step);
			break;
	}
}
function plot_histogram($function, $args, $width, $height, $xtitle, $ytitle, $charttitle, $xstart, $xend, $step){
	$data = getdata($function, $args, $xstart, $xend, $step);
	$barplot = new BarPlot($data[0], $data[1]);
	$barplot->SetWidth(5);
	$barplot->SetFillColor('blue');
	$graph = new Graph($width, $height);
	$graph->SetScale('linlin');
	$graph->Add($barplot);
	$graph->SetMargin(100,60,60,60);
	$graph->title->Set($charttitle);
	$graph->xaxis->title->Set($xtitle);
	$graph->yaxis->title->Set($ytitle);
	$graph->yaxis->SetTitleMargin(60);
	$graph->Stroke();
}

function plot_line($function, $args, $width, $height, $xtitle, $ytitle, $charttitle, $xstart, $xend, $step){
	$data = getdata($function, $args, $xstart, $xend, $step);
	$lineplot = new LinePlot($data[0], $data[1]);
	$lineplot->SetColor('blue');
	$lineplot->mark->SetType(MARK_FILLEDCIRCLE);
	$lineplot->mark->SetColor('blue');
	$lineplot->mark->SetFillColor('yellow');
	$graph = new Graph($width, $height);
	$graph->SetScale('linlin');
	$graph->Add($lineplot);
	$graph->SetMargin(100,60,60,60);
	$graph->title->Set($charttitle);
	$graph->xaxis->title->Set($xtitle);
	$graph->yaxis->title->Set($ytitle);
	$graph->yaxis->SetTitleMargin(60);
	$graph->Stroke();
}

function plot_scatter($function, $args, $width, $height, $xtitle, $ytitle, $charttitle, $xstart, $xend, $step){
	$data = getdata($function, $args, $xstart, $xend, $step);
	$splot = new ScatterPlot($data[0], $data[1]);
	$splot->SetColor('blue');
	$splot->mark->SetType(MARK_FILLEDCIRCLE);
	$splot->mark->SetColor('blue');
	$splot->mark->SetFillColor('yellow');
	$splot->mark->SetWidth(4);
	$splot->link->Show();
	$splot->link->SetStyle('dotted');
	$graph = new Graph($width, $height);
	$graph->SetScale('linlin');
	$graph->Add($splot);
	$graph->SetMargin(100,60,60,60);
	$graph->title->Set($charttitle);
	$graph->xaxis->title->Set($xtitle);
	$graph->yaxis->title->Set($ytitle);
	$graph->yaxis->SetTitleMargin(60);
	$graph->Stroke();
}

/**
 * Prepare the data to plot for a function. This returns up to 500 data points.
 * @param $function The function to plot
 * @param $args Optional arguments to pass to the function
 * @param $xstart Where to start plotting.
 * @param $xend Where to end plotting. Set PLOT_RANGE_AUTO to end on a plateau.
 * @param $step The difference between x-coordinates of two adjacent data points.
 */
function getdata($function, $args, $xstart, $xend, $step){
	$datay = array();
	$datax = array();
	
	if($xend == PLOT_RANGE_AUTO){
		$max = -1;
		$plateau_count = 0;
		for($i = 0, $x = $xstart; $i < 500; $i++, $x += $step){
			if($diff * 1000 < $max)
				$plateau_count++; //must have 4 consecutive data points at about the same value (diff < 0.1$ of max) to terminate.
			else
				$plateau_count = 0; //reset if it's not a true plateau.
			if($plateau_count >= 5) break;
			
			if($args === null){
				$datay[$i] = $function($x);
			}
			else{
				$datay[$i] = $function($args, $x);
			}
			
			if($i == 0){
				$max = $datay[0];
				$diff = $max;
			}
			else{
				$diff = abs($datay[$i] - $datay[$i-1]);
				$max = ($max > $datay[$i] ? $max : $datay[$i]);
			}
			$datax[$i] = $x;
		}
	}
	else {
		for($i = 0, $x = $xstart; $i < 500 && $x <= $xend; $x += $step){
			if($args == null){
				$datay[$i] = $function($x);
			}
			else{
				$datay[$i] = $function($args, $x);
			}
			$datax[$i] = $x;
		}
	}
	return array($datay, $datax);
}
?>