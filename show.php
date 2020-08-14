<?php

session_start();

if (isset($_POST['submit'])) {
    $_SESSION['login'] = $_POST['login'];
    $_SESSION['pass'] = $_POST['pass'];
	header('Location: show.php');
}
if (isset($_POST['s_destroy'])) {
	session_destroy();
	header('Location: show.php');
}

if (isset($_POST['clearmove'])) {
	unset($_SESSION['MoveFrom']);
	unset($_SESSION['FigToMove']);
	unset($_SESSION['MoveDst']);
	unset ($_GET['celltoid']);
	unset($_SESSION['MovePreview']);
	header('Location: show.php');
}
if (!isset($_SESSION['login']) || !isset($_SESSION['pass'])) {
    echo "Log in, please";
	echo "<form method='POST' action='' name='auth'>
		<label for='login'>Логин</label>
		<input type='text' id='login' name='login'></input></br>
		<label for='pass'>Пароль</label>
		<input type='password' id='pass' name='pass'></input></br>
		</br>
		<input type='submit' value='Войти' name='submit'>
		</form>
	";
}
else {
echo $_SESSION['login']."__".$_SESSION['pass']."<br />";
$auth = base64_encode($_SESSION['login'].':'.$_SESSION['pass']);
$context = stream_context_create([
    "http" => [
        "header" => "Authorization: Basic $auth"
    ]
]);
//Получаем от API JSON с расположением фигур и метаданными
$json = file_get_contents('http://site0.metamath.ru/api/state/', false, $context);
$obj = json_decode($json);
echo "<link rel='stylesheet' href='style.css'>";
//Get MoveFrom if move started
if (isset($_GET['cellfromid'])){
	$_SESSION['MoveFrom'] = $_GET['cellfromid'];
}
if (isset($_GET['figmove'])){
	$_SESSION['FigToMove'] = $_GET['figmove'];
}
if (isset($_GET['celltoid'])){
	$_SESSION['MoveDst'] = $_GET['celltoid'];
}
//Рисуем поле
echo "<table rows=8 cols=8 border=1 id='chboard'>";
$ltr = Array(1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F', 7 => 'G', 8 => 'H');
for ($row = 8; $row > 0; $row--) {
	echo "<tr id='r".$row."'>";
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

echo "<label for='MoveId'><h3>Номер хода</h3></label>";
echo "<div id='MoveId'></div>";

echo "<label for='cmd'><h3>Предыдущий ход</h3></label>";
echo "<div id='cmd'></div>";

echo "<label for='ret'><h3>Выбывшие фигуры</h3></label>";
echo "<div id='ret'></div>";
//Разбираем JSON
$array = (array) $obj;
$arr = json_decode(json_encode($array['board_state']), TRUE);
if ((isset($_SESSION['FigToMove'])) && (isset($_SESSION['MoveDst']))) {
	$arr[$_SESSION['FigToMove']] = $_SESSION['MoveDst'];
	$_SESSION['MovePreview'] = true;
}
//Размещаем фигуры
foreach ($arr as $figure => $fld) {
	if (($figure == 'cmd') && ($fld == ""))
		echo "
			<script type='text/javascript'>
				document.getElementById('cmd').innerHTML = 'N/A';
			</script>";
	echo "
	<script type='text/javascript'>
		document.getElementById('".strval($fld)."').innerHTML = document.getElementById('".strval($fld)."').innerHTML + '".strval($figure)."';
		if (document.getElementById('ret').innerHTML != '')
			document.getElementById('ret').innerHTML = document.getElementById('ret').innerHTML + ' ';
	</script>";
	if ($figure == 'MoveId')
		echo "
			<script type='text/javascript'>
				document.getElementById('MoveId').innerHTML = '".strval($fld)."';
			</script>";
	if (isset($_SESSION['MoveFrom']))
	{
		if ($fld == $_SESSION['MoveFrom'])
			echo "
				<script type='text/javascript'>
					document.getElementById('".strval($fld)."').innerHTML = '!' + '".strval($figure)."' + '!';
					if (document.getElementById('".strval($fld)."').classList.contains('wh'))
						document.getElementById('".strval($fld)."').classList.add('chosen');					
					else
						document.getElementById('".strval($fld)."').classList.add('chosenbl');
				</script>";
	}
}
//Make all occupied cells clickable
if ((!isset($_SESSION['MoveFrom'])) && (!isset($_SESSION['MoveDst'])))
	foreach ($arr as $figure => $fld) {
		if ($fld != 'ret')
			echo "<script type='text/javascript'>
				document.getElementById('".strval($fld)."').innerHTML = document.getElementById('".strval($fld)."').innerHTML + '<br/><a href=show.php?cellfromid=".strval($fld)."&figmove=".strval($figure).">move</a>';
				</script>";
	}
else if ((isset($_SESSION['MoveFrom'])) && (!isset($_SESSION['MoveDst']))){
	unset($arr['MoveTime']);
	unset($arr['MoveId']);
	unset($arr['U']);
	foreach ($arr as $figure => $fld) {
		$keyonly[] = strval($fld);
	}
	//print_r ($keyonly);
	//echo $_SESSION['MoveFrom'];
	
	for ($row = 8; $row > 0; $row--) {
		foreach ($ltr as $key => $r) {
			$myid = $r.$row;
			//echo $myid."++";
			if (!in_array($myid, $keyonly))
			{
				//echo $myid.'__';
				echo "<script type='text/javascript'>
					document.getElementById('".strval($myid)."').innerHTML = '<a href=show.php?celltoid=".strval($myid).">put</a>';
					</script>";
			}

		}
	}
}

else if ((isset($_SESSION['MoveFrom'])) && (isset($_SESSION['MoveDst']))){
	echo 'Moved!';
}
		//<a href='show.php?move=''>Take</a>

//Modify array element with moved figure

//get POST string
unset($arr['MoveTime']);
unset($arr['MoveId']);
unset($arr['U']);
$istring = http_build_query($arr, ',');
$istring = str_replace('&','","',$istring);
$istring = str_replace('=','":"',$istring);
$istring = '{"'.$istring;
$istring = $istring.'"}';
//echo $istring;
echo '<br/>From: '.$_SESSION['MoveFrom'];
echo '<br/>To: '.$_SESSION['MoveDst'];
echo '<br/>Fig: '.$_SESSION['FigToMove'];

if ($_SESSION['MovePreview'] == true) {
echo "<form method='POST' action='' name='clmove'>
		<input type='submit' value='Сбросить ход' name='clearmove'>
		</form>
	";
}

echo "<form method='POST' action='' name='deauth'>
		<input type='submit' value='Выйти' name='s_destroy'>
		</form>
	";
}
?>