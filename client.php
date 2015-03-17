<?

	require('conf.inc.php');

	session_start();
	$SID = session_id();
	//questo deve stare prima dell'inclusione di ws-client.js
	$debug = isset($_GET['debug']);

?><!DOCTYPE html>
<head>
<meta charset="utf-8" />

<title>Client per debug</title>


<script>
var debug = <?=$debug?'true':'false'?>,
	ws,
	ws_server = '<?=$hostws?>',
	ws_port = <?=$portws?>,
	SID = "<?=$SID?>";
</script>
</head>

<body<?=$debug?' id="debugbody"':''?>>

<h6>WebSocket Simple Client</h6>
<span>client per debug</span>

<hr /><br /><br />
<span id="sid">SID: </span><br />
<input id="conn" type="button" value="Connect" />&nbsp;
<input id="disc" type="button" value="Disconnect" />
<br />
<input id="tx" type="text" value="" size="54" />
<input id="send" type="button" value="Send" />
<br /><br />
<label>History:<label><br />
<select id="history" size="6" multiple="multiple" style="width:400px">
<option>{mov:'b1', x:300, y:200}</option>
</select>
<div id="log">&nbsp;</div>
<br />
<label>From Server:</label>
<br />
<textarea id="output" cols="60" rows="16">
Testo ricevuto...
</textarea>

<script src="/js/jquery-1.4.2.min.js"></script>
<script src="funcs.js"></script>
<script>
<?
	session_start();
	$SID = session_id();
	//questo deve stare prima dell'inclusione di ws-client.js
?>
var SID = "<?=$SID?>";
</script>
<script src="ws-client.js"></script>
<script language="javascript" type="text/javascript">

$(function() {	//ready

	function logga(mes)
	{
		$('#log').html(mes+'<br>');
	}

	var jsoninit = {updateall:'', allpos:''};  //stringa json inviata 

	$('#sid').append(SID);
	$('#tx').val( jsonStringify(jsoninit) );

	var ws_opts = {
		onopen: function() {
			logga("Connesso!");
		},
		onclose: function() {
			logga("Disconnesso!");
		},
		onmessage: function(data) {
			$('#output').append(data+'<br>');
			console.log(data);
			console.log("eval: ");
		}
	};

	function send() {
		var text = ''+$('#tx').val();

		ws_send(  text  );
		$('<option>').text( $('#tx').val() ).prependTo('#history');
		$('#tx').val('');
	}
	
	$('#history option').live('click',function() {
		$('#tx').val( $(this).text() );
	});
    $('#conn').bind('click',function(e) {
    	ws_connect(ws_opts);
    });
    $('#disc').bind('click',function(e) {
    	ws_disconnect();
    });    
    $('#send').bind('click',function(e) {
    	send();
    });
	$('#tx').bind("keydown", function(e) {
		if(e.keyCode == 13)	//premi invio
			send();
	});
});


</script>

</html>

