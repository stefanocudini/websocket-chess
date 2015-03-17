//var ws = require("websocket-server");
var sys = require('sys')
	,path = require('path')
	,conf = require('./conf').conf
	,net = require('net')
//  , ws = require('../lib/ws/server');
	,ws = require('./websocket-server/lib/ws/server');

var server = ws.createServer({debug: false}),//crea http server
	clients = [];

function logga(msg) {
	sys.puts(msg.toString());
};

//////crea una connessione verso server.php
var streamphp = net.createConnection(conf.portphp, conf.hostphp);
//streamphp.setEncoding('utf8');
streamphp.addListener("connect", function () {
	logga('CONNESSO A SERVER.PHP');
	streamphp.write('{"GET": {"connesso": "'+ path.basename(__filename) +'","sid": "123"}}');
});
streamphp.addListener("data", function (data) {
	server.broadcast(data);
	//FARE IN MODO CHE un richista allpos sia inviata solo a chi la richiede!!!
	logga('SERVER.PHP: '+ data);
});
streamphp.addListener("error", function () {
	//server.close();
	logga('DISCONNESSO DA SERVER.PHP');
});
//////crea una connessione verso il server.php

server.addListener("connection", function(socket) {
	
	logga('CLIENT CONNECT: '+socket.id);
	
	//messaggio dal browser!
	socket.addListener("message", function(msg) {
		logga('CLIENT: '+ msg+"\n");
		
		if(streamphp.readyState=='open')
			streamphp.write(msg);
		else
		{
			logga('SERVER.PHP non connesso');
		}
//		socket.send(msg);	//echo
//		server.send(conn.id,msg);  //stessa cosa di conn.send
	});

	socket.addListener("close", function(msg) {
		logga('CLIENT DISCONNECT: '+socket.id);
		//streamphp.write(msg);
	});	
});

server.listen(conf.portws, conf.hostws, function() {
	logga('BIND TO '+conf.hostws+':'+conf.portws );
});

