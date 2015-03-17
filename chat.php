<?

include('emoticons.php');

$chatfile = 'chat.txt';

if(isset($_GET['chat']))  //richiede righe della chat
{
	echo getchat();
	exit(0);
}
if(isset($_POST['chat']))
{
	$message = htmlentities(stripslashes(strip_tags(utf8_decode($_POST['mes']))), ENT_QUOTES);
	$name = htmlentities(stripslashes(strip_tags(utf8_decode($_POST['nom']))), ENT_QUOTES);
	putchat($name,$message);
	exit(0);
}

if(isset($_GET['color']))  //colore assegnato all'ip (DEBUG)
{
	?><h1 style="color:<?=getcolor()?>">Colore per tuo Nickname</h1><?
	exit(0);
}

if( isset($_GET['clearchat']) ) // (DEBUG)
{
	unlink('chat.txt');
	sleep(1);
	header("Location: ./");
	exit(0);
}

function getcolor()  //genera un colore per ogni utente collegato
{
#session_start();
#$turnval = session_id();  //valore usato come identificativo del turno
//per distinguere i giocatori con lo stesso ip si usa la sessine
//usare questo invece di REMOTE_ADDR

	list($a,$r,$g,$b) = explode('.',$_SERVER['REMOTE_ADDR']);
	return sprintf("#%02x%02x%02x",$r,$g,$b);
}

function getchat()
{
    global $emoticons;
 
	$righe = array();

	if( !is_file($chatfile) )
		$dati = array();
	elseif( isset($_GET['allchat']) or (filemtime($chatfile)>(time()-5)) )//modificata negli ultimi 5 secondi
		$dati = readChat();

	foreach($emoticons as $emo)
		$emoticonshtml[] = "<img src='emoticons/$emo' />";
	$emoticonskey = array_keys($emoticons);

	foreach($dati as $k=>$row)
		$dati[$k] = str_replace($emoticonskey,$emoticonshtml, $row);


	foreach($dati as $riga)
		$righe[] = '['.str_replace(':=:',',',$riga).']';

	return utf8_encode('['.implode(',',$righe).']');  //array di oggetti JSON
}

function putchat($name,$message)
{	
	$time = date('H:m:s');
	$options = '"color":"'.getcolor().'"';
	#$dati = "<small>$time</small> <big>$name:</big> <span>$message</span>";
	$dati = utf8_encode(sprintf('"%s":=:"%s":=:"%s":=:{%s}', $time,$name,$message,$options));

	// !!! non toccare questi utf8_decode/utf8_encode
	// che se sbraca tutto l'ambaradam di codifiche

	writeChat($dati);

}

function readChat()
{
	if(!is_file($chatfile))
		return array();

	$rows = file($chatfile);
	$dati = $rows;
	#$fid = fopen($chatfile,"r");
	#$dati = fgets($fid);
	#fclose($fid);

	return $dati;
}

function writeChat($dati)
{
	$maxrow = 5;

	if( is_file($chatfile) and count(file($chatfile))>$maxrow )
	{
		$rr = readChat();

		for($i=count($rr); $i>count($rr)-$maxrow; $i--)
			$b[]= $rr[$i-1];

		unlink($chatfile);
		sleep(1);
		$fid = fopen($chatfile,"a");
		fwrite($fid, implode('',array_reverse($b)));
		fclose($fid);
	}

	$fid = fopen($chatfile,"a");
	fwrite($fid, "$dati\n");
	fclose($fid);
}
?>

<div id="chatcontainer">
<!--iframe id="chatarea" frameborder="0" src="chat.html#end"></iframe-->
<form id="chatform" method="post" action="">
	<div style="float:left; margin-right:2px"><input type="text" name="nom" value="Nickname" size="8" /></div>
	<div style="float:left; margin-right:2px"><input type="text" name="mes" value="" size="58" /></div>
	<div id="setmoticons">
	  <span class="sel"><img src="emoticons/icon_applause.gif" /></span>
		<div id="emoticons">
		<?
			foreach($emoticons as $k=>$icon)
				echo '<a href="#" alt="'.$k.'"><img src="emoticons/'.$icon.'" /></a>';
		?>
		<em>x</em>
		</div>
	</div>
	<div style="float:left; margin-right:2px"><input type="submit" name="chat" value="invia" /></div>
</form>
<div id="chatarea"></div>
</div>

