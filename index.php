<?php
require_once('plot.php');

session_start();

$action = $_REQUEST['action'];

switch($action){
	case 'plot':
		plot_data();
		break;
	case 'data':
		print_data();
		break;
	case 'tableupload':
		table_upload();
		break;
	case 'defaultvalues':
		print_default();
		break;
	default:
		print_index();
		break;
}

function table_upload(){
	header("Content-type: application/json");
	if(isset($_REQUEST['table'])){
		$_SESSION['table'] = $_REQUEST['table'];
		echo '{ "token" : "' . md5($_SESSION['table']) . '" }';
	}
	else {
		echo '{ "token" : "" }';
	}
	
}

function print_default(){
	header('Content-type: text/javascript');
?>
	var defaults = {
	'xfrom': 0,
	'xto': 'auto',
	'yfrom': 'auto',
	'yto': 'auto',
	'step': 'auto',
	'mean': <?php echo PLOT_DEFAULT_MEAN;?>,
	'minreads': <?php echo PLOT_DEFAULT_MINREADS;?>,
	'size': <?php echo PLOT_DEFAULT_SIZE;?>,
	'ratio': <?php echo PLOT_DEFAULT_RATIO;?>,
	'oddsratio': <?php echo PLOT_DEFAULT_ODDSRATIO;?>,
	'controls': <?php echo PLOT_DEFAULT_CONTROLS;?>,
	'overhead': <?php echo PLOT_DEFAULT_OVERHEAD;?>,
	'sequencecost': <?php echo PLOT_DEFAULT_SEQUENCECOST;?>,
	'cutoff': <?php echo PLOT_DEFAULT_CUTOFF;?>
	};
<?php
}

function print_index(){
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>Optimal design and Power Estimation for Rare variant Association (OPERA) </title>
<script type="text/javascript" src="index.php?action=defaultvalues"></script>
<script type="text/javascript" src="index.js"></script>
<style type="text/css"><!--
.hint {
color: grey
}
--></style>
</head>
<body>
<center>
<h4>Optimal design and Power Estimation for Rare variant Association (OPERA)</h4>
</center>
<label for="distibution">Distribution of depth coverage:</label>
<select id="distribution" onchange="ondistributionchange()">
<option value="poisson" selected="selected">Poisson</option>
<option value="negativebinomial">Negative binomial</option>
<option value="table">Custom table of power vs. depth coverage</option>
</select><br/>
<label for="action">Plot:</label><select id="action" onchange="onactionchange()">
<option value="distribution" selected="selected" id="opt_distribution">distribution of depth coverage</option>
<option value="power">power to detect variant</option>
<option value="mincarrier">minimum proportion of carriers vs. number of cases</option>
<option value="mincarrier-both">minimum proportion of carriers in cases vs. total number of samples, sequencing both cases and controls</option>
<option value="power-from-case-frequency">power vs. number of cases, given frequency of variant in cases</option>
<option value="power-from-control-frequency">power vs. number of cases, given frequency of variant in controls and odds ratio</option>
<option value="power-from-case-frequency-both">power vs. total number of samples, sequencing both cases and controls, given frequency of variant in cases</option>
<option value="power-from-control-frequency-both">power vs. total number of samples, sequencing both cases and controls, given frequency of variant in controls and odds ratio</option>
</select><br/>
<div id="actionparams">
<label for="mean">Average depth-coverage:</label>
<input type="text" id="mean" class="hint" value="<?php echo PLOT_DEFAULT_MEAN;?>" onfocus="input_onfocus('mean')" onblur="input_onblur('mean')"/>
</div>
<div id="distributionparams">
</div><br/>
Plot: <br/>
<label for="xfrom">X axis: from </label><input type="text" id="xfrom" size="6" value="0" />
<label for="xto"> to </label><input type="text" id="xto" size="6"/>
<label for="step"> step </label><input type="text" id="step" size="6"/>
<label for="yfrom">Y axis: from </label><input type="text" id="yfrom" size="6" value="" />
<label for="yto"> to </label><input type="text" id="yto" size="6" value=""/>
<input type="button" value="Plot" onclick="doplot()" /><br/>
<div id="Plotpending"></div>
<div id="Plot"></div>
<div id="Plotlink"></div>
</body>
</html>
<?php 
}
?>