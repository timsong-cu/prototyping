<?php
require_once('../jpgraph/jpgraph.php');
require_once('../jpgraph/jpgraph_bar.php');
require_once('../jpgraph/jpgraph_line.php');
require_once('../jpgraph/jpgraph_scatter.php');
require_once('util.php');

define('PLOT_HISTOGRAM', '1');
define('PLOT_LINE', '2');
define('PLOT_SCATTER', '3');
define('PLOT_SCATTER_MULTIPLE', '4');
define('PLOT_AXIS_AUTO', '0xFFFF');
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
 * @param $ymin The minimum value of the Y axis; set to PLOT_AXIS_AUTO to have the minimum automatically determined.
 * @param $ymax The maximum value of the Y axis; set to PLOT_AXIS_AUTO to have the maximum automatically determined.
 */

function plot($type, $data, $width = 500, $height=500, $xtitle = "", $ytitle = "", $charttitle = "",  $ymin = PLOT_AXIS_AUTO, $ymax = 1){
	switch($type){
		case PLOT_HISTOGRAM:
			plot_histogram($data, $width, $height, $xtitle, $ytitle, $charttitle, $ymin, $ymax);
			break;
		case PLOT_LINE:
			plot_line($data, $width, $height, $xtitle, $ytitle, $charttitle, $ymin, $ymax);
			break;
		case PLOT_SCATTER:
			plot_scatter($data, $width, $height, $xtitle, $ytitle, $charttitle, $ymin, $ymax);
			break;
		case PLOT_SCATTER_MULTIPLE:
			plot_scatter_multiple($data, $width, $height, $xtitle, $ytitle, $charttitle, $ymin, $ymax);
			break;
	}
}
function plot_histogram($data, $width, $height, $xtitle, $ytitle, $charttitle, $ymin, $ymax){
	$barplot = new BarPlot($data[0], $data[1]);
	$barplot->SetWidth(5);
	$barplot->SetFillColor('blue');
	$graph = new Graph($width, $height);
	if($ymin == PLOT_AXIS_AUTO && $ymax == PLOT_AXIS_AUTO)
		$graph->SetScale('linlin');
	else if($ymin == PLOT_AXIS_AUTO){
		$graph->SetScale('linlin');
		$graph->yaxis->scale->SetAutoMax($ymax);
	}
	else if($ymax == PLOT_AXIS_AUTO){
		$graph->SetScale('linlin');
		$graph->yaxis->scale->SetAutoMin($ymax);
	}
	else
		$graph->SetScale('linlin', $ymin, $ymax);
	$graph->Add($barplot);
	$graph->SetMargin(100,60,60,60);
	$graph->title->Set($charttitle);
	$graph->xaxis->title->Set($xtitle);
	$graph->yaxis->title->Set($ytitle);
	$graph->yaxis->SetTitleMargin(60);
	$graph->Stroke();
}

function plot_line($data, $width, $height, $xtitle, $ytitle, $charttitle, $ymin, $ymax){
	$lineplot = new LinePlot($data[0], $data[1]);
	$lineplot->SetColor('blue');
	$lineplot->mark->SetType(MARK_FILLEDCIRCLE);
	$lineplot->mark->SetColor('blue');
	$lineplot->mark->SetFillColor('yellow');
	$graph = new Graph($width, $height);
	if($ymin == PLOT_AXIS_AUTO && $ymax == PLOT_AXIS_AUTO)
		$graph->SetScale('linlin');
	else if($ymin == PLOT_AXIS_AUTO){
		$graph->SetScale('linlin');
		$graph->yaxis->scale->SetAutoMax($ymax);
	}
	else if($ymax == PLOT_AXIS_AUTO){
		$graph->SetScale('linlin');
		$graph->yaxis->scale->SetAutoMin($ymax);
	}
	else
		$graph->SetScale('linlin', $ymin, $ymax);
	$graph->Add($lineplot);
	$graph->SetMargin(100,60,60,60);
	$graph->title->Set($charttitle);
	$graph->xaxis->title->Set($xtitle);
	$graph->yaxis->title->Set($ytitle);
	$graph->yaxis->SetTitleMargin(60);
	$graph->Stroke();
}

function plot_scatter($data, $width, $height, $xtitle, $ytitle, $charttitle, $ymin, $ymax){
	$splot = new ScatterPlot($data[0], $data[1]);
	$splot->SetColor('blue');
	$splot->mark->SetType(MARK_FILLEDCIRCLE);
	$splot->mark->SetColor('blue');
	$splot->mark->SetFillColor('yellow');
	$splot->mark->SetWidth(2);
	$splot->link->Show();
	$splot->link->SetStyle('dotted');
	$graph = new Graph($width, $height);
	if($ymin == PLOT_AXIS_AUTO && $ymax == PLOT_AXIS_AUTO)
		$graph->SetScale('linlin');
	else if($ymin == PLOT_AXIS_AUTO){
		$graph->SetScale('linlin');
		$graph->yaxis->scale->SetAutoMax($ymax);
	}
	else if($ymax == PLOT_AXIS_AUTO){
		$graph->SetScale('linlin');
		$graph->yaxis->scale->SetAutoMin($ymax);
	}
	else
		$graph->SetScale('linlin', $ymin, $ymax);
	$graph->Add($splot);
	$graph->SetMargin(100,60,60,60);
	$graph->title->Set($charttitle);
	$graph->xaxis->title->Set($xtitle);
	$graph->yaxis->title->Set($ytitle);
	$graph->yaxis->SetTitleMargin(60);
	$graph->Stroke();
}

function plot_scatter_multiple($data, $width, $height, $xtitle, $ytitle, $charttitle, $ymin, $ymax){
	$graph = new Graph($width, $height);
	if($ymin == PLOT_AXIS_AUTO && $ymax == PLOT_AXIS_AUTO)
		$graph->SetScale('linlin');
	else if($ymin == PLOT_AXIS_AUTO){
		$graph->SetScale('linlin');
		$graph->yaxis->scale->SetAutoMax($ymax);
	}
	else if($ymax == PLOT_AXIS_AUTO){
		$graph->SetScale('linlin');
		$graph->yaxis->scale->SetAutoMin($ymax);
	}
	else
		$graph->SetScale('linlin', $ymin, $ymax);
	$graph->SetMargin(100,60,60,60);
	
	$colors = array('blue', 'green', 'red', 'orange', 'yellow', 'black', 'darkgreen', 'sandybrown', 'lightblue');
	$count = count($data);
	for($i = 0; $i < $count; $i++){
		$series = $data[$i];
		//var_dump($series);
		$splot = new ScatterPlot($series['y'], $series['x']);
		$splot->SetColor($colors[$i % 9]);
		$splot->mark->SetType(MARK_FILLEDCIRCLE);
		$splot->mark->SetColor($colors[$i % 9]);
		$splot->mark->SetFillColor($colors[($i + 4) % 9]);
		$splot->mark->SetWidth(2);
		$splot->link->Show();
		$splot->link->SetStyle('dotted');
		$splot->SetLegend($series['legend']);
		$graph->Add($splot);
	}
	$graph->title->Set($charttitle);
	$graph->xaxis->title->Set($xtitle);
	$graph->yaxis->title->Set($ytitle);
	$graph->yaxis->SetTitleMargin(60);
	$graph->legend->SetPos(0.8, 0.9, 'left', 'bottom');
	$graph->Stroke();
}
?>