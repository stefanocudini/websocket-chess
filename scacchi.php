<?

header("Content-type: text/plain");

require('conf.inc.php');

#$inwebsocket = isset($json['GET']);
$inwebsocket = isset($argv);//se avviato da riga di comando allora e' incluso ed eseguito da cli.php o server.php
//funzionamento websocket

if(!$inwebsocket)// SE scacchi.php VIENE CHIAMATO VIA AJAX
//gira le richieste da ajax al server websocket
{
	//senno' le mosse dei client polling non vengono viste in tempo reale dai client websocket
	session_start();  //in caso di websocket, la sid viene passata nella richiesta json inviato
	//per distinguere i giocatori con lo stesso ip si usa la sessione
	$_get = $_GET;
	if(!isset($_get['sid']))
		$_get['sid'] = session_id();

	#$sock = @fsockopen($hostws, $portws, $errno, $errstr, 2);
	$sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	$res = @socket_connect($sock, $hostws, $portws);

	//si possono dirottare le richiete dei client polling sul server per websocket!!
	//quindi non eseguoi piu' direttament scacchi_start, ma richiamo il server via socket
	//ma il server.php serve una connessione alla volta, quindi e' sempre e solo impegnato a servire http-server.js
	//quindi devo mandare il pacchetto a http-server.js sulla porta 9000
	if($res!=false)	//SE NON RISPONDE IL SERVER WEBSOCKET, torna al funzionamento normale ajax
	{
		$_get['sid'] = 'AJAX.'.$_get['sid'];
		$json = json_encode(array('GET'=>$_get));  //crea pacchetto json dai parametri $_GET
		$path = dirname($_SERVER["PHP_SELF"]).'/index.php';
		$head =
		"GET ".$path." HTTP/1.1"."\r\n".
		"Host: ".$hostws."\r\n".
		"Upgrade: WebSocket"."\r\n".
		"Connection: Upgrade"."\r\n".
		"Origin: http://".$hostws.$path."\r\n"."\r\n";
		#"Content-Length: ".strlen($json)."\r\n"."\r\n";

		////WebSocket handshake http://www.whatwg.org/specs/web-socket-protocol/
		socket_write($sock, $head);

		socket_recv($sock, $wshead, 2048, 0);
#echo $wshead;//debug		
		socket_write($sock,"\x00$json\xff");
		
		socket_recv($sock, $wsdata, 2048, MSG_PEEK);  //contiene i dati tra "\x00$json\xff"
#echo $wsdata;//debug	
		socket_write($sock, str_repeat("\x00",9) );
		
		echo trim($wsdata,"\x00\xff");  //rimossi
		////WebSocket handshake
		
		socket_close($sock);
	}
	else  //il websocket server non risponde eseguo scacchi_start direttamente e mando in stdout quello che genera
	{
		#echo "ERROR: $errno, $errstr\n";
		echo scacchi_start($_get);
	}
}//gira le richieste ajax dal php al server websocket

//fine esecuzione

//--------------------

//inizione definizione funzioni

function scacchi_start($_get)
{
	global $inwebsocket;
	global $refresh; //se levi sta riga va tutto in loop!!
	global $debug;
	global $chat;
	global $turnval;
	global $defaultPos;
	
	$debug = isset($_get['debug']) ? true : false;
	$chat = isset($_get['nochat']) ? false : true;
	$refresh = false;#"./"	//forza il refresh della pagina dal server, per debug e aggiormenti
	
	$turnval = isset($_get['sid']) ? $_get['sid'] : 'sid-null';	//$turnval puo' essere una qualunque stringa
	$defaultPos = defaultPos(); //elenco delle posizioni di default definite in fondo a questo file
	
	if(isset($_get['updateall']))	//unisce tutte le richieste
		$OUT = ($refresh!==false?'"refresh":"'.$refresh.'",':'').
				getpos(isset($_get['allpos'])) .','. getonline() .','. getmyturn();
	
	elseif(isset($_get['ordina'])) //posizioni di default(x:y) (DEBUG)
		$OUT = getordina( isset($_get['verso'])?$_get['verso']:null );  //verso = ori o ver
	
	elseif(isset($_get['pos']))  //restituisce la posizione delle ultime pedine spostate o di tutte
		$OUT = getpos( isset($_get['allpos']) );
	
	elseif(isset($_get['mov']))  //sposta la pedina
		$OUT = putmov($_get['mov'], intval($_get['x']), intval($_get['y']) );//ritorna getpos()
	
	elseif(isset($_get['online']))
		$OUT = getonline();

	elseif(isset($_get['myturn']))  //stabilisce turno per muovere
		$OUT = getmyturn();

	else
		$OUT = '"error":"parametri non supportati"';

	return "{".$OUT."}";
}
	  //INVIARE E LA POSIZIONE ANCHE QUANDO SI TRASCINA IL MOUSE
	  //MA FARE IN MODO CHE NON VENGA SALVATA SU SERVER
	  //E SALVARE LA POSIZIONE SUL SERVER SOLO QUANDO SI RILASCIA IL MOUSE

////////////funzioni core

function getordina($verso='ori')
{
	global $defaultPos;
	
	$defaultPos = defaultPos($verso); //elenco delle posizioni di default in fondo a questo file
	//reimposta quale verso utilizzare
	
	foreach(array('b','n') as $C)
	{
		for($i=1; $i<=16; $i++)
		{
			$id = $C.$i;
			$dati = $defaultPos[$id];
			writePos($id, $dati);
		}
	}
	return getpos();//ritorna tutte posizioni
}

function getmyturn()
{
    global $turnval;
    global $turnfile;
  
	$fn = file($turnfile);  //nomefile

    if(trim($fn[0])==$turnval)
    	$ret = "no";
	else
		$ret = "yes";
	
	return '"turn":"'.$ret.'"';
}

//aggiungere funzione che restituisce posizione senza salvarla su disco
//usata solo nel caso di websocket, nel momento in cui si trascina la pedina

function putmov($id,$x,$y)
{
	writePos($id,"$x:$y");	//se restituisce false incoporare anche un messaggio di errore nella rispsta
	return getpos();  //controlla su disco la posizione delle ultime pedine spostate
}

function getpos($allpos=false)	//restituisce la posizione di tutte le pedine spostate, o tutte
{
	global $defaultPos;
	global $pedfile;

	$P = $O[] = array();
	
	foreach(array('b','n') as $C)
	{
		for($i=1; $i<=16; $i++)
		{
			$id = $C.$i;				  //id dato dal colore e numero
			$fn = sprintf($pedfile,$id);  //nome file di posizione della pedina
			
			if( !is_file($fn) ) //se il file e' stato cancellato, FORSE INUTILE! vedi: getordina()
			{
				$dati = $defaultPos[$id];
				writePos($id, $dati);
			}
			elseif( $allpos or (filemtime($fn)>(time() - 10 )) )  //spostare sta cosa dentro readPos()
			// se l'utente è appena entrato e richiedete tutte le posizioni
			// o se ci sono stati spostamenti da qualche secondo fa
			{
				$dati = readPos($id);
			}
			else
				continue;
			
			list($x, $y) = explode(':', $dati);
			$P[filemtime($fn)][] = array($id, (int)$x, (int)$y);//dopo un getordina, tutte le pedine hanno lo stesso identico $fm!!!
			#$P[] = array($id, (int)$x, (int)$y);
		}
	}

	krsort($P);	//ordina secondo la data di modifica della pedina!!! che e' meglio!
	//la prima e' quella modificata per ultima
	foreach($P as $fm=>$ar)	//qui le posizioni sono raggruppate per $fm
		foreach($ar as $a)
			$O[]= $a;
//	header("Content-type: text/plain");
//	print_r($P);
//	print_r($O);
//	exit(0);	

	$ret = '"pos":'.json_encode($O);  //toglie gli indici
	return $ret;
}//getpos

function getonline()
{
	$printOnline = false;
	include_once('online.php');
	$ret = isset($Online) ? $Online : 0; //definita in online.php

	return '"online":'.$ret;
}

function writePos($id,$dati)
{
	global $pedfile;
	
	$fn  = sprintf($pedfile,$id);  //nomefile
	$fid = fopen($fn,"w");
	$ret = fwrite($fid, $dati) ? true : false;
	fclose($fid);
	@chmod($fn,0775);
	if($ret)
		setTurno();

	return $ret;
}

function readPos($id)
{
	global $pedfile;
	
	$fn = sprintf($pedfile,$id);  //nomefile
	if(!is_file($fn))
		return false;
	$fid = fopen($fn,"r");
	$dati = fgets($fid);
	fclose($fid);

	return $dati;
}

function setTurno()
{
    global $turnval;
    global $turnfile;
    
	$fid = fopen($turnfile,"w");
	fwrite($fid, $turnval);
	fclose($fid);
	@chmod($turnfile,0775);
}

function defaultPos($verso='ori')
{
	$ord['ori'] = array(//////ordinamento destra/sinistra
		'b1'  => '12:0',    'n1'  => '509:0',  
		'b2'  => '10:51',   'n2'  => '506:51', 
		'b3'  => '16:134',  'n3'  => '507:134',
		#'b4'  => '11:177',  'n4'  => '505:177',
		'b4'  => '0:177',   'n4'  => '490:177', //con scritte
		'b5'  => '12:240',  'n5'  => '505:240',
		'b6'  => '14:337',  'n6'  => '505:337',
		'b7'  => '13:402',  'n7'  => '505:402',
		'b8'  => '17:483',  'n8'  => '505:483',
		'b9'  => '92:15',   'n9'  => '438:15', 
		'b10' => '86:81',   'n10' => '437:81', 
		'b11' => '86:155',  'n11' => '436:155',
		'b12' => '90:224',  'n12' => '433:224',
		'b13' => '89:288',  'n13' => '438:288',
		'b14' => '88:363',  'n14' => '437:363',
		'b15' => '88:433',  'n15' => '439:433',
		'b16' => '88:505',  'n16' => '440:505',
		);
	$ord['ver'] = array(//////ordinamento sopra/sotto
		'b16' => '21:87',	'b15' => '91:87',
		'b14' => '161:87',	'b13' => '231:87',
		'b12' => '301:87',	'b11' => '371:87',
		'b10' => '441:87',	'b9'  => '511:87',
		'b8'  => '17:7',	'b7'  => '85:0',
		'b6'  => '157:5',	'b5'  => '225:-10',
		'b4'  => '281:-7',	'b3'  => '367:5',
		'b2'  => '435:0',	'b1'  => '507:7',
		'n16' => '21:437',	'n15' => '91:437',
		'n14' => '161:437',	'n13' => '231:437',
		'n12' => '301:437',	'n11' => '371:437',
		'n10' => '441:437',	'n9'  => '511:437',
		'n8'  => '17:497',	'n7'  => '85:490',
		'n6'  => '157:495',	'n5'  => '225:480',
		'n4'  => '281:483',	'n3'  => '367:495',
		'n2'  => '435:490',	'n1' => '507:497',
		);
	return 	$ord[$verso];
}













?>
