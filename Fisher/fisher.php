<?php header("Content-type:text/html"); ?>
<html>
<head> <title>Fisher's exact test calculator</title></head>
<body>
<center>
<h4>Compute Fisher's exact test on a 2x2 contingency table</h4>
<?php
require_once('fishercalc.php');
if($_SERVER['REQUEST_METHOD'] != 'POST') {
?>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
<table>
	<tr>
		<th></th>
		<th>X<sub>1</sub></th>
		<th>X<sub>2</sub></th>
	</tr>
	<tr>
		<th>Y<sub>1</sub></th>
		<td><input type="text" name="n11" id="n11" ></td>
		<td><input type="text" name="n21" id="n21" ></td>
	</tr>
	<tr>
		<th>Y<sub>2</sub></th>
		<td><input type="text" name="n12" id="n12" ></td>
		<td><input type="text" name="n22" id="n22" ></td>
	</tr>
</table>
<label for="scale">Number of digits after decimal place to display: </label>
<input type="text" name="scale" id="scale" value="15" /> <br />
<input type="submit" value="Calculate using Fisher's exact test" />
</form>
<?php
}
else{
	$n11 = intval($_POST['n11']);
	$n21 = intval($_POST['n21']);
	$n12 = intval($_POST['n12']);
	$n22 = intval($_POST['n22']);
	$scale = intval($_POST['scale']);
	if(!$scale){
		$scale = 15;
	}
?>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
<table>
	<tr>
		<th></th>
		<th>X<sub>1</sub></th>
		<th>X<sub>2</sub></th>
	</tr>
	<tr>
		<th>Y<sub>1</sub></th>
		<td><input type="text" name="n11" value="<?php echo $n11;?>"></td>
		<td><input type="text" name="n21" value="<?php echo $n21;?>"></td>
	</tr>
	<tr>
		<th>Y<sub>2</sub></th>
		<td><input type="text" name="n12" value="<?php echo $n12;?>"></td>
		<td><input type="text" name="n22" value="<?php echo $n22;?>"></td>
	</tr>
</table>
<label for="scale">Number of digits after decimal place to display: </label>
<input type="text" name="scale" id="scale" value="<?php echo $scale;?>" /> <br />
<input type="submit" value="Calculate using Fisher's exact test" /> <br/ >
</form>
<?php
	echo 'By Fisher\'s exact test, <i>p</i>= ';
	echo fishertest_faster($n11, $n21, $n12, $n22);
}
?>
</center>
</body>
</html>