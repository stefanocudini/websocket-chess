#!/usr/bin/env php
<?

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

require('scacchi.php');

#echo scacchi_start(array('updateall'=>'','allpos'=>''));
//invia tutte le posizioni se per caso viene riavviato

if(defined('STDIN'))
while(true):

	$input = fread(STDIN, 2048);
	//es.
	//{"GET": {"updateall":"", "allpos":""} }
	if($input==false or strtolower(trim($input))=="quit")
		break;

#@file_put_contents("php://stderr", print_r($input,true) );	
	
	$json = json_decode(trim($input),true);

	if(is_array($json) and isset($json['GET']))	//il GET ci vuole sempre!
	
		echo scacchi_start($json['GET']);
		//LOGICA DEL GIOCO
	
	else
		echo '{"error":"parametri non supportati"}';

	#$log = date("[d/M/Y:H:i:s O] ").trim($input)."\n";	
	#file_put_contents('cli.log', $log, FILE_APPEND);	

endwhile;
?>
