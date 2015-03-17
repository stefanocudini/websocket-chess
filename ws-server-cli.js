
var sys = require('util'),
	path = require('path'),
	fs = require('fs'),
	//net = require('net'),	//per connessione socket verso server.php
    proc = require('child_process'),	//per connessone cli verso cli.php
	ws = require('./websocket-server/lib/ws/server');

var conf = JSON.parse( fs.readFileSync('./conf.json') );

var server = ws.createServer({debug: false}),//crea http server
	clients = [];

function logga(msg) {
	sys.puts(msg.toString());
};

//esegue cli.php che utilizza scacchi.php

//////crea una connessione cli verso il cli.php//
var streamphp = proc.spawn('./cli.php');

streamphp.stdout.setEncoding('utf8');
//RISOLTO BUG INCOMPRENSIBILE: http://goo.gl/8babT


//per chiuderlo usare streamphp.stdin.end();
streamphp.stdout.on('data', function (data) {

	logga('CLI.PHP: ' + data +"\n");
	server.broadcast(data);		//quello che ritorna cli.php lo manda ai clients
//		server.manager.find( id, callbac
//usa questo per mandare allpos solo a client che lo ha richiesto

});
streamphp.on('exit', function (code) {
	logga('DISCONNESSO DA CLI.PHP');
});
streamphp.stderr.on('data', function (e) {
	logga('CLI.PHP ERROR: '+e);
});
//////crea una connessione cli verso il server.php//

server.addListener("connection", function(socket) {
	
	logga('CLIENT CONNECT: '+socket.id);
	logga("NCLIENT: "+server.manager.length+"\n");

	socket.addListener("message", function(msg) {	//dati dai clients
		logga('CLIENT: '+ msg +"\n");
		
		streamphp.stdin.write(msg);	//cli
	});

	socket.addListener("close", function(msg) {
		logga('CLIENT DISCONNECT: '+socket.id);
	});
});

server.listen(conf.portws, conf.hostws, function() {
	logga('BIND TO '+conf.hostws+':'+conf.portws );
});

