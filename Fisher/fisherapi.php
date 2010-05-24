<?php
	require_once('fishercalc.php');
	/**
	 * API for fisher's exact test:
	 * must pass action=fisher
	 * takes n11, n12, n21, n22 and scale (optional, default 15) either GET or POST
	 * outputs json formatted object - only field is "p" - 
	 * sample output:
	 * { "p" : "0.00040000" }
	 */
	if($_REQUEST['action'] != 'fisher')
		exit();
	else
		api_fisher();
	
	function api_fisher(){
		header("Content-type:application/json");
		$n11 = intval($_REQUEST['n11']);
		$n21 = intval($_REQUEST['n21']);
		$n12 = intval($_REQUEST['n12']);
		$n22 = intval($_REQUEST['n22']);
		$scale = intval($_REQUEST['scale']);
		if(!$scale){
			$scale = 15;
		}
		
		$ret = fishertest_faster($n11, $n21, $n12, $n22, $scale);
		echo "{ \"p\" : \"$ret\" }";
	}
?>