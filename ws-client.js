/*

vedi qui:
	http://dev.w3.org/html5/websockets/
*/

function ws_send( json ) {
/*console.log("dentro ws_send: ");
console.log(typeof json);	
console.log(json);
*/
	json.sid = SID;  //incapsula la sessione definita in index.php
	var get = {"GET":json}; //pacchetto standard inviato al server, con GET e property sid
	//sid serve a conservare la sessione anche per richieste websocket
	//e' definita in index.php presa da session_id()
	if(ws && ws.readyState == 1)
		ws.send( jsonStringify(get) );
}

function ws_connect(calls) {
    try {
	    ws = new WebSocket('wss://'+ ws_server +':'+ ws_port +'/');
    }
    catch(e) {
        console.warn('websocket connection error')
    }
	ws.onopen = function(e) {
		calls.onopen(e);		
	};
	
	ws.onmessage = function(e) {
		calls.onmessage(e.data);
	};

	ws.onerror = function(e) {
		calls.onerror(e);
	};

	ws.onclose = function(e) {
		calls.onclose(e);
	};

	return ws;
}

function ws_disconnect() {
	if(ws)
	{
		ws.close();
	}
}

