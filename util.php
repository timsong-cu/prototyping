<?php
require_once('math.php');

define('PLOT_RANGE_AUTO', -1);
define('PLOT_DISCARD', 0xFFFF);
define('MAXIMUM_CACHE_SIZE', 100); // maximum number of cache files.
define('CACHE_SIZE', 70); // number of cache files that should remain after cleanup.

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
function get_data($params, $function, $args, $xstart, $xend, $step, $nocache = false){
	$hash = md5($params);
	if(!file_exists("cache/$hash") || $nocache)
		$cache = array();
	else{
		// use cache and file also exists.
		$result = @file_get_contents ("cache/$hash");
		if($result){
			$cache = @unserialize($result);
		}
		if(!$cache) $cache = array();
	}
	$datay = array();
	$datax = array();
	
	if($xend == PLOT_RANGE_AUTO){
		$max = -1;
		$plateau_count = 0;
		$discard_count = 0;
		$diff = 0;
		for($i = 0, $index = 0, $x = $xstart; $i < 5000; $x += $step){
			if($diff * 1000 < $max)
				$plateau_count++; //must have 30 consecutive data points at about the same value (diff < 0.1$ of max) to terminate.
			else
				$plateau_count = 0; //reset if it's not a true plateau.
			if($plateau_count >= 10 && $plateau_count * $step >= 30) break;
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
			if($result == PLOT_DISCARD){
				$discard_count ++;
				if($discard_count >= 50) break;
				continue;
			}
			else
				$discard_count = 0;
				
			
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
			$datax[$index] = $x;
			$datay[$index] = $result;
			$index ++; 
		}
	}
	@file_put_contents("cache/$hash", serialize($cache));
	@maintain_cache();
	return array($datay, $datax);
}

function maintain_cache(){
	$files = scandir('./cache');
	array_splice($files, 0, 2); //remove . and ..
	if(count($files) <= MAXIMUM_CACHE_SIZE)
		return; //nothing to be done here
	usort($files, 'filetime_comparator');
	for($i = 0; $i < count($files) - CACHE_SIZE; $i++)
		@unlink('cache/'.$files[$i]); // remove the oldest files.	
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

function filetime_comparator($first, $second){
	$firsttime = @filemtime("cache/$first");
	$secondtime = @filemtime("cache/$second");
	if(!$firsttime && !$secondtime) return 0; // if neither exists, they are equal
	else if(!$firsttime) return -1; //otherwise the nonexistent file is smaller
	else if(!$secondtime) return 1;
	else return $firsttime - $secondtime; //otherwise the file modified (and thus accessed) earlier is smaller.
}
?>
