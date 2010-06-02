<?php
require_once('../fisher/fishercalc.php');

define('PLOT_RANGE_AUTO', '-1');
define('PLOT_DISCARD', '0xFFFF');

function get_mincount($controls, $total, $cutoff){
/*
	Table:
			var		normal
	case	$min	$total-$min
	ctrl.	0		$controls
*/
	for($i = $total; $i >= 0; $i--){
		if(fishertest_fast($i, $total-$i, 0, $controls) > $cutoff)
			return ($i+1)> $total? -1 : $i+1;
	}
	return 0; // should never get here.
}

/**
 * Prepare the data to plot for a function. This returns up to 1000 data points.
 * @param $params A unique identifier of $function and $args.
 * @param $function The function to plot
 * @param $args Optional arguments to pass to the function
 * @param $xstart Where to start plotting.
 * @param $xend Where to end plotting. Set PLOT_RANGE_AUTO to end on a plateau.
 * @param $step The difference between x-coordinates of two adjacent data points.
 */
function getdata($params, $function, $args, $xstart, $xend, $step, $nocache = false){
	if(function_exists('sqlite_open')){
		$db = init_database();
		if(!$nocache){
			$ret = get_cached_data($db, $params);
			$discovered = false;
			$doupdate = false;
			foreach($ret as $row){
				//uae cached data if possible
				$rangemin = $row['min'];
				$rangemax = $row['max'];
				if($rangemin <= $xstart && $rangemax >= $xend){
					// results are usable directly
					$discovered = true;
					$datay = unserialize($row['y']);
					$datax = unserialize($row['x']);
					list($rety, $retx) = getdata_in_range($datay, $datax, $xstart, $xend);
				}
				else if($rangemax < $xstart || ($rangemin > $xend && $xend != PLOT_RANGE_AUTO)){
					// No overlap at all. Do nothing.
				}
				else if($rangemax >= $xend){
					$datay = unserialize($row['y']);
					$datax = unserialize($row['x']);
					list($rety1, $retx1,,$max) = getdata_nocache($function, $args, $xstart, $rangemin - $step, $step);
					if(abs($rangemin-$step - $max) < $step){
						$doupdate = true; // partial overlap, on the smaller side.
						$discovered = true;
						list($rety, $retx) = getdata_in_range($datay, $datax, $xstart, $xend);
						$rety = array_merge($rety1, $rety);
						$retx = array_merge($retx1, $retx);
						$datax = array_merge($retx1, $datax);
						$datay = array_merge($rety1, $datay);
					}
					else{
						$rety = $rety1;
						$retx = $retx1;
						$discovered = true;
					}
				}
				else {
					$datay = unserialize($row['y']);
					$datax = unserialize($row['x']);
					list($rety1, $retx1,, $max) = getdata_nocache($function, $args, $rangemax + $step, $xend, $step);
					if(abs($xend - $max) < $step){
						$doupdate = true; // partial overlap, on the larger side.
						$discovered = true;
						list($rety, $retx) = getdata_in_range($datay, $datax, $xstart, $xend);
						$rety = array_merge($rety, $rety1);
						$retx = array_merge($retx, $retx1);
						$datax = array_merge($datax, $retx1);
						$datay = array_merge($datay, $rety1);
					}
					else{
						list($rety, $retx) = getdata_in_range($datay, $datax, $xstart, $xend);
						$discovered = true;
					}
				}
				if($discovered) break;
			}
		}
		if($nocache || !$discovered){
			list($rety, $retx, $min, $max) = getdata_nocache($function, $args, $xstart, $xend, $step);
			sqlite_exec($db, 'INSERT INTO data (params, min, max, x, y)
			VALUES (\'' . sqlite_escape_string($params) . "\',$min,$max,\'" . sqlite_escape_string(serialize($retx)) . '\',\'' . sqlite_escape_string(serialize($rety)) .'\')' );
		}
		else if($doupdate){
			if($xend != PLOT_RANGE_AUTO){
				sqlite_exec($db, "UPDATE data
				SET min=$xstart,max=$xend,x=\'" . sqlite_escape_string(serialize($datax)) . '\',y=\''. sqlite_escape_string(serialize($datay)) .'\'
				WHERE params=\''.sqlite_escape_string($params).'\'');
			}
			else {
				sqlite_exec($db, "UPDATE data
				SET min=$xstart,max=$rangemax,x=\'" . sqlite_escape_string(serialize($datax)) . '\',y=\''. sqlite_escape_string(serialize($datay)) .'\'
				WHERE params=\''.sqlite_escape_string($params).'\'');
			}
		}
		sqlite_close($db);
		return array($rety, $retx);
	}
	else{	
		$hash = md5($params);
		if(!file_exists("cache/$hash") || $nocache){
			$result = getdata_nocache($function, $args, $xstart, $xend, $step);
			$file = fopen("cache/$hash", "w");
			fwrite($file, serialize($result));
			fclose($file);
			return array($result[0], $result[1]);
		}
		else{
			// use cache and file also exists.
			$size = filesize("cache/$hash");
			$file = fopen("cache/$hash", "r");
			$result = unserialize(fread($file, $size));
			fclose($file);
			$datay = $result[0];
			$datax = $result[1];
			$rangemin = $result[2];
			$rangemax = $result[3];
			if($rangemin <= $xstart && $rangemax >= $xend){
			// results are usable directly
				list($rety, $retx) = getdata_in_range($result[0], $result[1], $xstart, $xend);
				return array($rety, $retx);
			}
			else if($rangemax < $xstart || ($rangemin > $xend && $xend != PLOT_RANGE_AUTO)){
				$result = getdata_nocache($function, $args, $xstart, $xend, $step);	
				$file = fopen("cache/$hash", "w");
				fwrite($file, serialize($result));
				fclose($file);
				return array($result[0], $result[1]);
			}
			else if($rangemax >= $xend){
				list($rety1, $retx1,,$max) = getdata_nocache($function, $args, $xstart, $rangemin - $step, $step);
				if(abs($rangemin-$step - $max) < $step){
					list($rety, $retx) = getdata_in_range($datay, $datax, $xstart, $xend);
					$rety = array_merge($rety1, $rety);
					$retx = array_merge($retx1, $retx);
					$datax = array_merge($retx1, $datax);
					$datay = array_merge($rety1, $datay);
					$file = fopen("cache/$hash", "w");
					fwrite($file, serialize(array($datay, $datax, $xstart, $rangemax)));
					fclose($file);
				}
				else{
					$rety = $rety1;
					$retx = $retx1;
				}
				return array($rety, $retx);
			}
			else {
				// partial overlap, on the larger side.
				list($rety1, $retx1,, $max) = getdata_nocache($function, $args, $rangemax + $step, $xend, $step);
				if(abs($xend - $max) < $step){
					list($rety, $retx) = getdata_in_range($datay, $datax, $xstart, $xend);
					$rety = array_merge($rety, $rety1);
					$retx = array_merge($retx, $retx1);
					$datax = array_merge($datax, $retx1);
					$datay = array_merge($datay, $rety1);
					$file = fopen("cache/$hash", "w");
					fwrite($file, serialize(array($datay, $datax, $rangemin, $xend)));
					fclose($file);
				}
				else{
					list($rety, $retx) = getdata_in_range($datay, $datax, $xstart, $xend);
				}
				return array($rety, $retx);
			}
		}
	} 
}


function getdata_nocache($function, $args, $xstart, $xend, $step){
	$datay = array();
	$datax = array();
	
	if($xend == PLOT_RANGE_AUTO){
		$max = -1;
		$plateau_count = 0;
		$diff = 0;
		for($i = 0, $index = 0, $x = $xstart; $i < 1000; $i++, $x += $step){
			if($diff * 1000 < $max)
				$plateau_count++; //must have 5 consecutive data points at about the same value (diff < 0.1$ of max) to terminate.
			else
				$plateau_count = 0; //reset if it's not a true plateau.
			if($plateau_count >= 5) break;
			if($args == null){
				$result = $function($x);
			}
			else{
				$result = $function($args, $x);
			}
			if($result == PLOT_DISCARD)
				continue;
			
			$datax[$index] = $x;
			$datay[$index] = $result; 
			if($index == 0){
				$max = $datay[0];
				$diff = $max;
			}
			else{
				$diff = abs($datay[$index] - $datay[$index-1]);
				$max = ($max > $datay[$index] ? $max : $datay[$index]);
			}		
			$index ++;
		}
	}
	else {
		for($i = 0, $index = 0, $x = $xstart; $i < 1000 && $x <= $xend; $i++, $x += $step){
			if($args == null){
				$result = $function($x);
			}
			else{
				$result = $function($args, $x);
			}
			if($result == PLOT_DISCARD)
				continue;
			
			$datax[$index] = $x;
			$datay[$index] = $result;
			$index ++; 
		}
	}
	return array($datay, $datax, $xstart, $x);
}

function getdata_in_range($datay, $datax, $xstart, $xend){
	$count = count($datax);
	$start = -1;
	$end = -1;
	for($i = 0; $i < $count; $i++){
		if($datax[$i] >= $xstart){
			$start = $i;
			break;
		}
		echo $i . "<br/>";
	}
	if($xend == PLOT_RANGE_AUTO) 
		$end = $count - 1;
	else{
		for($i = $count - 1; $i >= 0; $i--){
			if($datax[$i] <= $xend){
				$end = $i;
				break;
			}
		}
	}
	if($start == -1 || $end == -1) return array(array(), array());
	$datax = array_slice($datax, $start, $end - $start + 1);
	$datay = array_slice($datay, $start, $end - $start + 1);
	return array($datay, $datax);
}

function init_database(){
	if(!file_exists('data.db')){
		$db = sqlite_open('data.db');
		sqlite_exec($db, 'CREATE TABLE data(
		id INTEGER PRIMARY KEY,
		params TEXT,
		min FLOAT,
		max FLOAT,
		x TEXT,
		y TEXT');
	}
	else
		$db = sqlite_open('data.db');
	return $db;
}

function get_cached_data($db, $param){
	return sqlite_array_query($db, 'SELECT * FROM data WHERE params=\''
	. sqlite_escape_string($param) . '\'');
}
?>
