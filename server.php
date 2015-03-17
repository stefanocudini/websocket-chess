#!/usr/bin/env php
<?


error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

#ini_set('default_socket_timeout',3);
#echo 'TIMEOUT'.ini_get('default_socket_timeout');


require('scacchi.php');	//definisce: scacchi_start()

$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");

$server = @socket_bind($socket, $hostphp, $portphp) or die("Could not bind to $hostphp:$portphp\n");
echo "BIND TO $hostphp\n";

echo "LISTENING ON $portphp\n";
$result = socket_listen($socket, 3) or die("C'e' qualche altro programma in ascolta su sta porta\n");

while(true)
{
	$client = socket_accept($socket);

	echo "CLIENT CONNECTED\n";

	while(true)
	{
		$input = socket_read($client, 2048);
		
		if($input==false)  break;

		if(strtolower(trim($input))=="quit")
		{  
			echo "QUIT SIGNAL\n";
			socket_close($client);
			break 2;
		}

		echo "REQUEST: $input\n";
		$json = json_decode($input,true);
		
		if(is_array($json) and isset($json['GET']))
			$out = scacchi_start($json['GET']);
		else
			$out = 'metodo non supportato';

		echo "REPLY: $out\n";
				
		socket_write($client, $out, strlen($out)); //forse serve .chr(0); oppure "\n"
	}
	
	socket_close($client);
	echo "CLIENT DISCONNECTED\n\n";
}
echo "SERVER CLOSED\n\n";
socket_close($socket);

?>
