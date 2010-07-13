<?php
require_once('../fisher/fishercalc.php');

define('PLOT_RANGE_AUTO', '-1');
define('PLOT_DISCARD', '0xFFFF');

function get_mincount($controls, $total, $numberincontrols, $cutoff){
/*
	Table:
			var		normal
	case	$min	$total-$min
	ctrl.	$numberincontrols		$controls - $numberincontrols
*/
	for($start = 0, $end = $total, $cur = intval(round(($start+$end)/2)), $found=0;
		!$found;){
		if(!fishertest_cutoff($cur, $total-$cur, $numberincontrols, $controls - $numberincontrols, $cutoff)){
			$start = $cur + 1;
			$cur = intval(round(($start+$end)/2));
		}
		else{
			$prev = $cur - 1;
			if(!fishertest_cutoff($prev, $total-$prev, $numberincontrols, $controls - $numberincontrols, $cutoff))
				$found = true;
			else {
				$end = $cur - 1;
				$cur = intval(round(($start+$end)/2));
			}
		}
	}
	return ($cur > $total) ? -1 : $cur;
}

/**
 * Prepare the data to plot for a function. This returns up to 5000 data points.
 * @param $params A unique identifier of $function and $args.
 * @param $function The function to plot
 * @param $args Optional arguments to pass to the function
 * @param $xstart Where to start plotting.
 * @param $xend Where to end plotting. Set PLOT_RANGE_AUTO to end on a plateau.
 * @param $step The difference between x-coordinates of two adjacent data points.
 */
function getdata($params, $function, $args, $xstart, $xend, $step, $nocache = false){
	$hash = md5($params);
	if(!file_exists("cache/$hash") || $nocache)
		$cache = array();
	else{
		// use cache and file also exists.
		$size = filesize("cache/$hash");
		$file = fopen("cache/$hash", "r");
		$cache = unserialize(fread($file, $size));
		fclose($file);
	}
	$datay = array();
	$datax = array();
	
	if($xend == PLOT_RANGE_AUTO){
		$max = -1;
		$plateau_count = 0;
		$diff = 0;
		for($i = 0, $index = 0, $x = $xstart; $i < 5000; $x += $step){
			if($diff * 1000 < $max)
				$plateau_count++; //must have 30 consecutive data points at about the same value (diff < 0.1$ of max) to terminate.
			else
				$plateau_count = 0; //reset if it's not a true plateau.
			if($plateau_count >= 30) break;
			if(isset($cache[strval($x)]))
				$result = $cache[strval($x)];
			else{
				$i++;
				if(!isset($args)){
					$result = $function($x);
				}
				else{
					$result = $function($args, $x);
				}
				$cache[strval($x)] = $result;
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
		for($i = 0, $index = 0, $x = $xstart; $i < 5000 && $x <= $xend; $x += $step){
			if(isset($cache[strval($x)]))
				$result = $cache[strval($x)];
			else{
				$i++;
				if(!isset($args)){
					$result = $function($x);
				}
				else{
					$result = $function($args, $x);
				}
				$cache[strval($x)] = $result;
			}
			if($result == PLOT_DISCARD)
				continue;
			$cache[strval($x)] = $result;
			
			$datax[$index] = $x;
			$datay[$index] = $result;
			$index ++; 
		}
	}
	$file = fopen("cache/$hash", "w");
	fwrite($file, serialize($cache));
	fclose($file);
	return array($datay, $datax);
}

function array_bsearch( $needle, $haystack, $comparator , &$probe )
{
    $high = count( $haystack ) -1;
    $low = 0;
    
    while ( $low < $high )
    {
        $probe = floor( ( $high + $low ) / 2 );
        $comparison = $comparator( $haystack[$probe], $needle );
        if ( $comparison < 0 )
        {
            $low = $probe +1;
        }
        elseif ( $comparison > 0 ) 
        {
            $high = $probe -1;
        }
        else
        {
            return true;
        }
    }
    //The loop ended without a match 
    //Compensate for needle greater than highest haystack element
    if($comparator($haystack[count($haystack)-1], $needle) < 0)
    {
        $probe = count($haystack);
    }
    else if ($comparator($haystack[$low], $needle) < 0){
    	$probe = $low + 1;
    }
    else
    	$probe = $low;
    	
    return false;
}

function first_element_comparator($first, $second){
	return $first[0] - $second[0];	
}
?>
