<?

	require('conf.inc.php');

	session_start();
	$SID = session_id();
	//questo deve stare prima dell'inclusione di ws-client.js
	$debug = true;//isset($_GET['debug']);

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="https://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>La Scacchiera Web3.0</title>
<style type="text/css">
	@import url('scacchi.css');
	@import url('pedine.css');
	@import url('chat.css');
</style>
<script>
var debug = <?=$debug?'true':'false'?>,
	ws,
	ws_server = '<?=$hostws?>',
	ws_port = <?=$portws?>,
	SID = "<?=$SID?>";
</script>
</head>

<body<?=$debug?' id="debugbody"':''?>>
<div id="copy"><a href="https://opengeo.tech/">Labs</a> &bull; <a rel="author" href="https://opengeo.tech/stefano-cudini/">Stefano Cudini</a></div>
<a href="https://github.com/stefanocudini/websocket-chess"><img id="ribbon" src="https://s3.amazonaws.com/github/ribbons/forkme_right_darkblue_121621.png" alt="Fork me on GitHub"></a>



<div id="pager">

<div id="turno" style="display:none"><span>&Eacute; il tuo turno</span></div>
<div id="online">Giocatori: <span>1</span></div>

<div id="container">

	<div id="loader"></div>
	
	<? for($b=1; $b<=16; $b++): ?>
	<div id="pb<?=$b?>" class="pedina bianca" title="<?=$debug?$b:''?>"><div class="cur"></div></div>
	<? endfor; ?>

	<? for($n=1;$n<=16;$n++): ?>
	<div id="pn<?=$n?>" class="pedina nera" title="<?=$debug?$n:''?>"><div class="cur"></div></div>
	<? endfor; ?>

	<table id="scacchiera" cellspacing="0" cellpadding="0">
		<? for($r=1; $r<=8; $r++): ?>
		<tr>
		<? $i=0; for($c=1; $c<=8; $c++): $scura = ((++$i+$r)%2)>0; ?>
			<td id="<?=($cid=chr(96+$c).(9-$r))?>" class="cella <?=$scura?'scura':'chiara'?>"><?=$debug?$cid:''?></td>
		<? endfor; ?>
		</tr>
		<? endfor; ?>
	</table>

	<div id="tavolo">
		<div id="tavb" class="tav bianca"><em>Rimuovi pedina</em></div>
		<div id="tavn" class="tav nera"><em>Rimuovi pedina</em></div>
	</div>
	
	<div id="hist_wrap"><div id="history"></div></div>
</div>

<div id="status">&nbsp;</div>

<?
	#include('chat.php');
?>

<? if($debug): ?>
<div id="debug_wrap">
	<input type="button" value="updateChat()" />
	<input type="button" value="updatePedine()" />
	<input type="button" value="updateOnline()" />
	<input type="button" value="ordinaPedine()" />
	<input type="button" value="toggleVersoOrd()" />
	<input type="button" value="websocketStartStop()" />	
	<input type="button" value="pollingStartStop()" />
	&nbsp;
	<input type="button" value="clearLog()" />	
	
	<div id="log"></div>
</div>
<? endif; ?>

</div><!--#pager-->

<script src="/js/jquery-1.4.2.min.js"></script>
<script src="jquery-ui-1.8.9.custom.min.js"></script>
<script src="jquery.overlaps.js"></script>
<script src="jquery.easydrag.js"></script>
<script src="funcs.js"></script>
<script src="ws-client.js"></script>
<!--script src="chat.js"></script-->
<script src="scacchi.devel.js"></script>
<script type="text/javascript" src="/labs-common.js"></script>
</body>
</html>
