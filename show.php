<?php
$json = file_get_contents('http://chess1.metamath.ru/state/');
$obj = json_decode($json);
//print_r ($obj);
echo "<link rel='stylesheet' href='style.css'>";
echo "<table rows=8 cols=8 border=1>";
$ltr = Array(1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F', 7 => 'G', 8 => 'H');
for ($row = 8; $row > 0; $row--) {
	echo "<tr id=".$row.">";
	foreach ($ltr as $key => $r) {
		echo "<td id='".$r.$row."'"; 
		if ((($row%2 == 1) && ($key%2 == 1)) || (($row%2 == 0) && ($key%2 == 0)))
			echo "class='bl'>";
		else
			echo "class='wh'>";
		}
	echo "</tr>";
}
echo "</table>";
foreach ($obj[0] as $figure => $fld) {
	echo "
	<script type='text/javascript'>
		document.getElementById('".strval($fld)."').innerHTML = '".strval($figure)."';
	</script>";
}

?>